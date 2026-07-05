<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Appearance;
use App\Enums\ThemePreset;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Lab404\Impersonate\Services\ImpersonateManager;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passkeys\Passkey;
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
final class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasUuids;
    use Impersonate;
    use Notifiable;
    use PasskeyAuthenticatable;
    use TwoFactorAuthenticatable;

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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
