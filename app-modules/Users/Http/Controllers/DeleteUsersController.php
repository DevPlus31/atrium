<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Models\User;
use App\Modules\Concerns\DeletesSelection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Modules\Users\Actions\DeleteUser;
use Modules\Users\Http\Requests\DeleteUsersRequest;

final readonly class DeleteUsersController
{
    use DeletesSelection;

    /**
     * Delete the users selected in the table; rows the user may not delete
     * are left alone and counted in the message.
     */
    #[Authorize('viewAny', User::class)]
    public function __invoke(DeleteUsersRequest $request, DeleteUser $deleteUser): RedirectResponse
    {
        $this->deleteSelection(
            $request,
            $request->users(),
            $deleteUser->handle(...),
            static fn (int $count): string => trans_choice(':count user deleted.|:count users deleted.', $count),
        );

        return to_route('admin.users.index');
    }
}
