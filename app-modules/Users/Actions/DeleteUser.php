<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteUser
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->delete();

            activity('users')
                ->performedOn($user)
                ->event('deleted')
                ->withProperties([
                    'attributes' => ['name' => $user->name, 'email' => $user->email],
                ])
                ->log('deleted');
        });
    }
}
