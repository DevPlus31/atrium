<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\Repositories\UserRepository;
use Modules\Users\Domain\ValueObjects\Email;
use SensitiveParameter;

final readonly class CreateUser
{
    public function __construct(private UserRepository $users)
    {
        //
    }

    /**
     * @param  list<string>  $roles
     */
    public function handle(string $name, string $email, #[SensitiveParameter] string $password, array $roles, bool $verified = false): User
    {
        $email = (string) new Email($email);

        return DB::transaction(function () use ($name, $email, $password, $roles, $verified): User {
            $user = new User([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'email_verified_at' => $verified ? now() : null,
            ]);

            $this->users->save($user);

            $user->syncRoles($roles);

            activity('users')
                ->performedOn($user)
                ->event('created')
                ->withProperties([
                    'attributes' => ['name' => $name, 'email' => $email, 'roles' => $roles],
                ])
                ->log('created');

            // Mail goes out only once the account really exists (after the
            // outermost transaction commits, e.g. AcceptInvitation's).
            DB::afterCommit(static function () use ($user): void {
                event(new Registered($user));
            });

            return $user;
        });
    }
}
