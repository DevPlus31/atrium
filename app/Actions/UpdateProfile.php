<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Modules\AuditLog;
use Illuminate\Support\Facades\DB;

final readonly class UpdateProfile
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $user, array $attributes): void
    {
        DB::transaction(function () use ($user, $attributes): void {
            $old = $user->only(array_keys($attributes));
            $email = $attributes['email'] ?? null;

            if (is_string($email)) {
                $user->changeEmail($email);
            }

            $user->fill(array_diff_key($attributes, ['email' => true]))->save();

            AuditLog::record(
                log: 'users',
                event: 'updated',
                subject: $user,
                properties: ['old' => $old, 'attributes' => $attributes],
                causer: $user,
            );
        });
    }
}
