<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Domain\ValueObjects\Email;
use App\Models\User;
use App\Modules\AuditLog;
use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\Repositories\UserRepository;

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

            $user->fill(['name' => $name]);
            $user->changeEmail($email);

            $this->users->save($user);

            $user->syncRoles($roles);

            AuditLog::record(
                log: 'users',
                event: 'updated',
                subject: $user,
                properties: [
                    'old' => $old,
                    'attributes' => ['name' => $name, 'email' => $email, 'roles' => $roles],
                ],
            );

            return $user->refresh();
        });
    }
}
