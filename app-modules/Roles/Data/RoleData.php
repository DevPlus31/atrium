<?php

declare(strict_types=1);

namespace Modules\Roles\Data;

use Illuminate\Support\Facades\Auth;
use Modules\Roles\Policies\RolePolicy;
use Spatie\LaravelData\Data;
use Spatie\Permission\Models\Role;

final class RoleData extends Data
{
    /**
     * @param  list<string>  $permissions
     * @param  array{update: bool, delete: bool}  $can
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $permissions,
        public int $users_count,
        public bool $is_system,
        public string $created_at,
        public array $can,
    ) {
        //
    }

    public static function fromModel(Role $role): self
    {
        $viewer = Auth::user();

        /** @var list<string> $permissions */
        $permissions = $role->permissions->pluck('name')->sort()->values()->all();

        return new self(
            id: (string) $role->id,
            name: $role->name,
            permissions: $permissions,
            users_count: $role->users_count ?? $role->users()->count(),
            is_system: RolePolicy::isSystemRole($role),
            created_at: (string) $role->created_at?->toIso8601String(),
            can: [
                'update' => $viewer?->can('update', $role) ?? false,
                'delete' => $viewer?->can('delete', $role) ?? false,
            ],
        );
    }
}
