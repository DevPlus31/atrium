<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Modules\Users\Actions\DeleteUsers;
use Modules\Users\Http\Requests\DeleteUsersRequest;

final readonly class DeleteUsersController
{
    /**
     * Delete the users selected in the table; rows the user may not delete
     * are left alone and counted in the message.
     */
    #[Authorize('viewAny', User::class)]
    public function __invoke(DeleteUsersRequest $request, DeleteUsers $action): RedirectResponse
    {
        $users = $request->users();

        abort_if($users->isEmpty(), 403);

        $action->handle($users);

        $skipped = $request->skipped($users->count());
        $message = trans_choice(':count user deleted.|:count users deleted.', $users->count());

        Inertia::flash('toast', ['type' => 'success', 'message' => $skipped === 0
            ? $message
            : $message.' '.trans_choice(':count could not be deleted.|:count could not be deleted.', $skipped)]);

        return to_route('admin.users.index');
    }
}
