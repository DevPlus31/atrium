<?php

declare(strict_types=1);

namespace Modules\Roles\Http\Requests;

use App\Models\User;
use App\Rules\GrantablePermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Roles\Http\Requests\Concerns\ProvidesRoleInput;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class StoreRoleRequest extends FormRequest
{
    use ProvidesRoleInput;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Role::class) ?? false;
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        $actor = $this->user();

        assert($actor instanceof User);

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(Role::class, 'name')],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists(Permission::class, 'name'), new GrantablePermission($actor)],
        ];
    }
}
