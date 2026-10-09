<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\Repositories\UserRepository;
use Modules\Users\Domain\ValueObjects\Email;

final readonly class UpdateUser
{
    public function __construct(private UserRepository $users)
    {
        //
    }

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

            $user->fill([
                'name' => $name,
                'email' => $email,
                ...($emailChanged ? ['email_verified_at' => null] : []),
            ]);

            $this->users->save($user);

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
                DB::afterCommit(static function () use ($user): void {
                    $user->sendEmailVerificationNotification();
                });
            }

            return $user->refresh();
        });
    }
}
