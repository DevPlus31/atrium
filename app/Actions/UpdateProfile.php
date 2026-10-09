<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
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
            $emailChanged = isset($attributes['email']) && $user->email !== $attributes['email'];

            $user->update([
                ...$attributes,
                ...($emailChanged ? ['email_verified_at' => null] : []),
            ]);

            activity('users')
                ->causedBy($user)
                ->performedOn($user)
                ->event('updated')
                ->withProperties(['old' => $old, 'attributes' => $attributes])
                ->log('updated');

            if ($emailChanged) {
                DB::afterCommit(static function () use ($user): void {
                    $user->sendEmailVerificationNotification();
                });
            }
        });
    }
}
