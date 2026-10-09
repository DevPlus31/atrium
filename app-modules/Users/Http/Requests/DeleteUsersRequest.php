<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use App\Models\User;
use App\Modules\Concerns\ValidatesBulkSelection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteUsersRequest extends FormRequest
{
    use ValidatesBulkSelection;

    /**
     * The selected users the user may delete.
     *
     * @return Collection<int, User>
     */
    public function users(): Collection
    {
        return $this->permitted(User::query(), 'delete');
    }
}
