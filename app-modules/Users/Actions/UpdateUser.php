<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\ValueObjects\Email;

final readonly class UpdateUser
{
    /**
     * @param  list<string>  $roles
     */
    public function handle(User $user, string $name, string $email, array $roles): User
    {
        $email = (string) new Email($email);

        return DB::transaction(function () use ($user, $name, $email, $roles): User {
            $old = [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name')->sort()->values()->all(),
            ];

            $emailChanged = $user->email !== $email;

            $user->update([
                'name' => $name,
                'email' => $email,
                ...($emailChanged ? ['email_verified_at' => null] : []),
            ]);

            $user->syncRoles($roles);

            activity('users')
                ->performedOn($user)
                ->event('updated')
                ->withProperties([
                    'old' => $old,
                    'attributes' => ['name' => $name, 'email' => $email, 'roles' => $roles],
                ])
                ->log('updated');

            if ($emailChanged) {
                $user->sendEmailVerificationNotification();
            }

            return $user->refresh();
        });
    }
}
