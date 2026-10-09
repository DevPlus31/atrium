<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Models;

use App\Domain\Concerns\InteractsWithDomainEvents;
use App\Domain\Contracts\RecordsDomainEvents;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use Modules\Users\Database\Factories\InvitationFactory;
use Modules\Users\Domain\Events\InvitationAccepted;
use Modules\Users\Domain\Events\InvitationSent;
use Modules\Users\Domain\Exceptions\InvitationAlreadyAccepted;
use Modules\Users\Domain\Exceptions\InvitationNotPending;
use Modules\Users\Domain\ValueObjects\Email;

/**
 * An invitation to create an account with the given roles. The link in the
 * email is a signed URL; it stops working when the invitation expires, is
 * accepted, or is revoked (deleted).
 *
 * @property-read string $id
 * @property string $email
 * @property list<string> $roles
 * @property string|null $invited_by
 * @property CarbonInterface $expires_at
 * @property CarbonInterface|null $accepted_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User|null $inviter
 */
final class Invitation extends Model implements RecordsDomainEvents
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory;

    use HasUuids;
    use InteractsWithDomainEvents;

    public const int LIFETIME_DAYS = 7;

    /**
     * Invite an address into the given roles, valid for LIFETIME_DAYS.
     *
     * @param  list<string>  $roles
     */
    public static function issue(Email $email, array $roles, ?User $inviter): self
    {
        $invitation = new self();
        $invitation->setAttribute($invitation->getKeyName(), $invitation->newUniqueId());
        $invitation->email = (string) $email;
        $invitation->roles = $roles;
        $invitation->invited_by = $inviter?->id;
        $invitation->expires_at = now()->addDays(self::LIFETIME_DAYS);

        $invitation->recordThat(new InvitationSent($invitation->id));

        return $invitation;
    }

    /**
     * Invitations that can still be accepted.
     *
     * @return Builder<self>
     */
    public static function pending(): Builder
    {
        return self::query()->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    /**
     * Send it again with a fresh expiry; an accepted invitation is done.
     */
    public function renew(): void
    {
        if ($this->accepted_at !== null) {
            throw InvitationAlreadyAccepted::forEmail($this->email);
        }

        $this->expires_at = now()->addDays(self::LIFETIME_DAYS);

        $this->recordThat(new InvitationSent($this->id));
    }

    /**
     * Mark it used by the account just created; only once, and only before
     * it expires.
     */
    public function accept(User $user): void
    {
        $this->ensurePending();

        $this->accepted_at = now();

        $this->recordThat(new InvitationAccepted($this->id, $this->invited_by, $user->id, $user->name));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Fail fast, before any work is done for an invitation that cannot be
     * accepted any more.
     */
    public function ensurePending(): void
    {
        if (! $this->isPending()) {
            throw InvitationNotPending::forEmail($this->email);
        }
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    /**
     * Signed but not time-limited: the accept page checks the expiry itself
     * so an old link explains what happened instead of failing with a 403.
     */
    public function acceptUrl(): string
    {
        return URL::signedRoute('invitations.show', ['invitation' => $this->id]);
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'email' => 'string',
            'roles' => 'array',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): InvitationFactory
    {
        return InvitationFactory::new();
    }
}
