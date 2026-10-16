<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConsentRecord;
use App\Models\User;
use App\Modules\AuditLog;
use App\Modules\PersonalDataRegistry;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Laravel\Passkeys\Passkey;
use Laravel\Sanctum\PersonalAccessToken;
use NoDiscard;
use Spatie\Activitylog\Models\Activity;
use stdClass;

final readonly class ExportPersonalData
{
    public function __construct(private PersonalDataRegistry $modules)
    {
        //
    }

    /**
     * Everything the app holds about the user, as JSON-ready arrays (GDPR
     * Art. 15 and 20, the CCPA "right to know"): the account, its sign-in
     * methods, sessions, notifications, cookie choices, the audit entries
     * they caused or that concern them, and each module's records. Secrets
     * (password, 2FA secret, token hashes, passkey credentials) stay out. The
     * request itself is logged, as both laws ask for a record of requests.
     *
     * @return array<string, mixed>
     */
    #[NoDiscard]
    public function __invoke(User $user): array
    {
        // As stored: the instance passed in may not carry every column.
        $user = $user->fresh() ?? $user;

        $export = [
            'exported_at' => now()->toIso8601String(),
            'account' => $this->account($user),
            'roles' => $user->getRoleNames()->values()->all(),
            'passkeys' => $user->passkeys->map(static fn (Passkey $passkey): array => [
                'name' => $passkey->name,
                'created_at' => self::date($passkey->created_at),
                'last_used_at' => self::date($passkey->last_used_at),
            ])->values()->all(),
            'api_tokens' => $user->tokens->map(static fn (PersonalAccessToken $token): array => [
                'name' => $token->name,
                'abilities' => $token->abilities,
                'created_at' => self::date($token->created_at),
                'last_used_at' => self::date($token->last_used_at),
                'expires_at' => self::date($token->expires_at),
            ])->values()->all(),
            'sessions' => $user->browserSessions()->get()->map(static fn (stdClass $session): array => [
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_active' => is_numeric($session->last_activity) ? Date::createFromTimestamp((int) $session->last_activity)->toIso8601String() : null,
            ])->values()->all(),
            'notifications' => $user->notifications->map(static fn (DatabaseNotification $notification): array => [
                'data' => $notification->data,
                'read_at' => self::date($notification->read_at),
                'created_at' => self::date($notification->created_at),
            ])->values()->all(),
            'cookie_choices' => $user->consentRecords()->oldest('created_at')->get()->map(static fn (ConsentRecord $record): array => [
                'version' => $record->version,
                'granted' => $record->granted->pluck('value')->all(),
                'global_privacy_control' => $record->gpc,
                'created_at' => self::date($record->created_at),
            ])->values()->all(),
            'activity' => $this->activity($user),
            'modules' => $this->modules->export($user),
        ];

        DB::transaction(static function () use ($user): void {
            AuditLog::record(log: 'privacy', event: 'personal-data-exported', subject: $user, causer: $user);
        });

        return $export;
    }

    private static function date(?CarbonInterface $date): ?string
    {
        return $date?->toIso8601String();
    }

    /**
     * @return array<string, mixed>
     */
    private function account(User $user): array
    {
        $photo = $user->getFirstMediaUrl(User::AVATAR);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => self::date($user->email_verified_at),
            'profile_photo' => $photo === '' ? null : $photo,
            'locale' => $user->locale,
            'timezone' => $user->timezone,
            'appearance' => $user->appearance?->value,
            'theme' => $user->theme?->value,
            'layout' => $user->layout,
            'notify_by_email' => $user->notify_by_email,
            'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
            'terms_version' => $user->terms_version,
            'terms_accepted_at' => self::date($user->terms_accepted_at),
            'created_at' => self::date($user->created_at),
            'updated_at' => self::date($user->updated_at),
        ];
    }

    /**
     * The audit entries the user caused or that are about their account.
     * Their recorded values are left out: they can name other people.
     *
     * @return list<array<string, mixed>>
     */
    private function activity(User $user): array
    {
        return array_values(Activity::query()
            ->where(static fn (Builder $query): Builder => $query->whereMorphedTo('causer', $user)->orWhereMorphedTo('subject', $user))
            ->oldest('id')
            ->get()
            ->map(static fn (Activity $activity): array => [
                'log' => $activity->log_name,
                'event' => $activity->event,
                'by_you' => $activity->causer()->is($user),
                'created_at' => self::date($activity->created_at),
            ])
            ->all());
    }
}
