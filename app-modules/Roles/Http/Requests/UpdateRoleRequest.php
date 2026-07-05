<?php

declare(strict_types=1);

namespace Modules\Roles\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Roles\Policies\RolePolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class UpdateRoleRequest extends FormRequest
{
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

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Role::class, 'name')->ignore($role->id),
                function (string $attribute, mixed $value, Closure $fail) use ($role): void {
                    if (RolePolicy::isSystemRole($role) && $value !== $role->name) {
                        $fail(__('System roles cannot be renamed.'));
                    }
                },
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists(Permission::class, 'name')],
        ];
    }

    public function name(): string
    {
        /** @var string $name */
        $name = $this->validated('name');

        return $name;
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        /** @var list<string> $permissions */
        $permissions = $this->validated('permissions', []);

        return $permissions;
    }

    private function routedRole(): Role
    {
        $role = $this->route('role');

        assert($role instanceof Role);

        return $role;
    }
}
