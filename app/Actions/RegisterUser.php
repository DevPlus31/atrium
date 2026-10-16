<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\ValueObjects\Email;
use App\Models\User;
use App\Modules\AuditLog;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;

final readonly class RegisterUser
{
    public function __construct(private AcceptTerms $acceptTerms)
    {
        //
    }

    /**
     * Create a self-registered account. Signing up requires accepting the
     * Terms of Service, so the acceptance is recorded with it.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, #[SensitiveParameter] string $password): User
    {
        return DB::transaction(function () use ($attributes, $password): User {
            $email = $attributes['email'] ?? null;

            $user = User::query()->create([
                ...$attributes,
                ...(is_string($email) ? ['email' => (string) new Email($email)] : []),
                'password' => $password,
            ]);

            AuditLog::record(
                log: 'users',
                event: 'created',
                subject: $user,
                properties: ['attributes' => $user->only(['name', 'email'])],
                causer: $user,
            );

            ($this->acceptTerms)($user);

            DB::afterCommit(static function () use ($user): void {
                event(new Registered($user));
            });

            return $user;
        });
    }
}
