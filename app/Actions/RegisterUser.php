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
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes, #[SensitiveParameter] string $password): User
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

            DB::afterCommit(static function () use ($user): void {
                event(new Registered($user));
            });

            return $user;
        });
    }
}
