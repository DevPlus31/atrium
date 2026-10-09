<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final readonly class DeleteUsers
{
    public function __construct(private DeleteUser $deleteUser)
    {
        //
    }

    /**
     * Delete each of the users, all or none, exactly as one by one.
     *
     * @param  Collection<int, User>  $users
     */
    public function handle(Collection $users): void
    {
        DB::transaction(function () use ($users): void {
            foreach ($users as $user) {
                $this->deleteUser->handle($user);
            }
        });
    }
}
