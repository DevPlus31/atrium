<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Domain\ValueObjects\Email;
use App\Models\User;
use App\Modules\AuditLog;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\Repositories\UserRepository;
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

            AuditLog::record(
                log: 'users',
                event: 'created',
                subject: $user,
                properties: [
                    'attributes' => ['name' => $name, 'email' => $email, 'roles' => $roles],
                ],
            );

            // Mail goes out only once the account really exists (after the
            // outermost transaction commits, e.g. AcceptInvitation's).
            DB::afterCommit(static function () use ($user): void {
                event(new Registered($user));
            });

            return $user;
        });
    }
}
