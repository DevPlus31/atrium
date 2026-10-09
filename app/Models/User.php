<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Concerns\InteractsWithDomainEvents;
use App\Domain\Contracts\RecordsDomainEvents;
use App\Domain\ValueObjects\Email;
use App\Enums\Appearance;
use App\Enums\ThemePreset;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Lab404\Impersonate\Models\Impersonate;
use Lab404\Impersonate\Services\ImpersonateManager;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passkeys\Passkey;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read string $email
 * @property-read CarbonInterface|null $email_verified_at
 * @property-read string $password
 * @property-read string|null $remember_token
 * @property-read string|null $two_factor_secret
 * @property-read string|null $two_factor_recovery_codes
 * @property-read CarbonInterface|null $two_factor_confirmed_at
 * @property-read Appearance|null $appearance
 * @property-read ThemePreset|null $theme
 * @property-read array<string, string>|null $layout
 * @property-read string|null $locale
 * @property-read string|null $timezone
 * @property-read bool $notify_by_email
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Collection<int, Passkey> $passkeys
 */
#[Hidden([
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
])]
final class User extends Authenticatable implements HasLocalePreference, HasMedia, MustVerifyEmail, PasskeyUser, RecordsDomainEvents
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasUuids;
    use Impersonate;
    use InteractsWithDomainEvents;
    use InteractsWithMedia;
    use Notifiable;
    use PasskeyAuthenticatable;
    use TwoFactorAuthenticatable;

    /**
     * The role that holds every declared permission via the global
     * Gate::before hook; policy rules still apply to it.
     */
    public const string SUPER_ADMIN_ROLE = 'super-admin';

    /**
     * The role that grants entry to the admin panel by default (see the
     * PANEL_ABILITY gate).
     */
    public const string PANEL_ROLE = 'admin';

    /**
     * The media collection holding the profile photo.
     */
    public const string AVATAR = 'avatar';

    /**
     * The Gate ability that guards the admin panel: every module's admin
     * routes use `can:access-panel`, and it decides whether a user works in
     * the admin or the member area. Redefine the gate to change who enters
     * the panel (by permission, team, …) in one place.
     */
    public const string PANEL_ABILITY = 'access-panel';

    /**
     * The token of the current API request; null on the app's own pages.
     */
    private ?PersonalAccessToken $apiToken = null;

    /**
     * Move the account to another address (stored normalised). A new address
     * is unverified until its owner confirms it: the verification mail goes
     * out when the surrounding transaction commits, so call this inside the
     * one that saves the user. Whether the address changed.
     */
    public function changeEmail(string $email): bool
    {
        $email = (string) new Email($email);

        if ($email === $this->email) {
            return false;
        }

        $this->forceFill(['email' => $email, 'email_verified_at' => null]);

        DB::afterCommit(function (): void {
            $this->sendEmailVerificationNotification();
        });

        return true;
    }

    public function canAccessPanel(): bool
    {
        return $this->hasRole(self::PANEL_ROLE);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::SUPER_ADMIN_ROLE);
    }

    /**
     * The panel or super-admin role this user is the only holder of, if any:
     * deleting the account would leave nobody to run the app.
     */
    public function soleAdministrativeRole(): ?string
    {
        foreach ([self::PANEL_ROLE, self::SUPER_ADMIN_ROLE] as $role) {
            if ($this->hasRole($role) && self::query()->role($role)->count() === 1) {
                return $role;
            }
        }

        return null;
    }

    /**
     * The user's rows in the sessions table. Only the database session
     * driver keeps them; with any other driver the query finds nothing.
     */
    public function browserSessions(): Builder
    {
        $connection = Config::get('session.connection');

        return DB::connection(is_string($connection) ? $connection : null)
            ->table(Config::string('session.table'))
            ->where('user_id', $this->id);
    }

    /**
     * Sanctum calls this when a request authenticates with a token. The
     * token is also kept in a nullable property: Sanctum's own docblocks
     * claim a token is always present, which hides the session case.
     */
    public function withAccessToken(PersonalAccessToken $accessToken): static
    {
        $this->accessToken = $accessToken;
        $this->apiToken = $accessToken;

        return $this;
    }

    /**
     * Whether the API token this request came with grants the ability;
     * always true outside the API (no token).
     */
    public function tokenAllows(string $ability): bool
    {
        return ! $this->apiToken instanceof PersonalAccessToken || $this->apiToken->can($ability);
    }

    /**
     * Whether the user may manage (edit, delete) the given account: only a
     * super-admin may manage another super-admin, and anyone else only
     * accounts whose permissions they hold themselves, so taking over an
     * account (e.g. by changing its email) never widens what they can do.
     */
    public function canManage(self $user): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return ! $user->isSuperAdmin() && $this->holdsAllPermissionsOf($user);
    }

    /**
     * Whether the user may grant the given role: never the super-admin role
     * unless they hold it, and only roles whose permissions they hold.
     */
    public function canGrantRole(Role $role): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $role->name !== self::SUPER_ADMIN_ROLE
            && $role->permissions->every(fn (Permission $permission): bool => $this->can($permission->name));
    }

    /**
     * Whether the user may grant the given permission to a role.
     */
    public function canGrantPermission(string $permission): bool
    {
        return $this->can($permission);
    }

    /**
     * Whether the user holds every permission the given user has, so acting
     * as them (impersonation) never widens what this user can do.
     */
    public function holdsAllPermissionsOf(self $user): bool
    {
        return $user->getAllPermissions()->every(fn (Permission $permission): bool => $this->can($permission->name));
    }

    /**
     * Whether the user may impersonate other users.
     */
    public function canImpersonate(): bool
    {
        return $this->can('users.impersonate');
    }

    /**
     * Whether the user may be impersonated. Anyone who can impersonate —
     * including super-admins via the global Gate::before hook — is protected.
     */
    public function canBeImpersonated(): bool
    {
        return ! $this->can('users.impersonate');
    }

    /**
     * The profile photo: one image per user, replaced on upload.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::AVATAR)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * A square thumbnail, generated with the upload so it is ready at once.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections(self::AVATAR)
            ->nonQueued()
            ->fit(Fit::Crop, 256, 256)
            ->format('webp');
    }

    /**
     * The URL of the profile photo's thumbnail, or null without one.
     */
    public function avatarUrl(): ?string
    {
        $url = $this->getFirstMediaUrl(self::AVATAR, 'thumb');

        return $url === '' ? null : $url;
    }

    /**
     * The language the user chose; Laravel sends their notifications and
     * mail in it.
     */
    public function preferredLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * The given moment in the user's own timezone and language, for dates
     * the server writes for them (notifications, emails): their chosen
     * timezone, or the application's when they have not chosen one.
     */
    public function toUserTime(CarbonInterface $time): CarbonInterface
    {
        return $time->copy()
            ->setTimezone($this->timezone ?? Config::string('app.timezone'))
            ->locale($this->locale ?? Config::string('app.locale'));
    }

    /**
     * Start impersonating the given user.
     */
    public function impersonate(self $user): bool
    {
        return resolve(ImpersonateManager::class)->take($this, $user);
    }

    /**
     * Leave the current impersonation and restore the impersonator.
     */
    public function leaveImpersonation(): bool
    {
        return resolve(ImpersonateManager::class)->leave();
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'name' => 'string',
            'email' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'remember_token' => 'string',
            'two_factor_secret' => 'string',
            'two_factor_recovery_codes' => 'string',
            'two_factor_confirmed_at' => 'datetime',
            'appearance' => Appearance::class,
            'theme' => ThemePreset::class,
            'layout' => 'array',
            'locale' => 'string',
            'timezone' => 'string',
            'notify_by_email' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
