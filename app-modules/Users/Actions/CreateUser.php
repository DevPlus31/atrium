<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;

final readonly class CreateUser
{
    /**
     * @param  list<string>  $roles
     */
    public function handle(string $name, string $email, #[SensitiveParameter] string $password, array $roles, bool $verified = false): User
    {
        return DB::transaction(function () use ($name, $email, $password, $roles, $verified): User {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'email_verified_at' => $verified ? now() : null,
            ]);

            $user->syncRoles($roles);

            activity('users')
                ->performedOn($user)
                ->event('created')
                ->withProperties([
                    'attributes' => ['name' => $name, 'email' => $email, 'roles' => $roles],
                ])
                ->log('created');

            event(new Registered($user));

            return $user;
        });
    }
}
