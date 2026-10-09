<?php

declare(strict_types=1);

namespace Modules\Roles\Http\Requests;

use App\Models\User;
use App\Rules\GrantablePermission;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Roles\Domain\ValueObjects\RoleName;
use Modules\Roles\Http\Requests\Concerns\ProvidesRoleInput;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class UpdateRoleRequest extends FormRequest
{
    use ProvidesRoleInput;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->routedRole()) ?? false;
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        $role = $this->routedRole();
        $actor = $this->user();

        assert($actor instanceof User);

        /** @var list<string> $held */
        $held = $role->permissions()->pluck('name')->all();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Role::class, 'name')->ignore($role->id),
                // The same rule UpdateRole enforces, asked up front for a friendly error.
                function (string $attribute, mixed $value, Closure $fail) use ($role): void {
                    if (is_string($value) && mb_trim($value) !== '' && ! new RoleName($role->name)->canBecome(new RoleName($value))) {
                        $fail(__('System roles cannot be renamed.'));
                    }
                },
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists(Permission::class, 'name'), new GrantablePermission($actor, $held)],
        ];
    }

    private function routedRole(): Role
    {
        $role = $this->route('role');

        assert($role instanceof Role);

        return $role;
    }
}
