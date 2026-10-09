<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
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
            $user = User::query()->create([
                ...$attributes,
                'password' => $password,
            ]);

            activity('users')
                ->causedBy($user)
                ->performedOn($user)
                ->event('created')
                ->withProperties(['attributes' => $user->only(['name', 'email'])])
                ->log('created');

            DB::afterCommit(static function () use ($user): void {
                event(new Registered($user));
            });

            return $user;
        });
    }
}
