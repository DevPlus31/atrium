<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\Repositories\UserRepository;

final readonly class DeleteUser
{
    public function __construct(private UserRepository $users)
    {
        //
    }

    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->users->delete($user);

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
