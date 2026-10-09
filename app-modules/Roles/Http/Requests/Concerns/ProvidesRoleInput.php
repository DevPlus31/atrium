<?php

declare(strict_types=1);

namespace Modules\Roles\Http\Requests\Concerns;

use App\Modules\Concerns\ReadsValidatedInput;

/**
 * Typed accessors for the validated role form, shared by create and update
 * (their rules differ: update protects system roles and held permissions).
 */
trait ProvidesRoleInput
{
    use ReadsValidatedInput;

    public function name(): string
    {
        return $this->validatedString('name');
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
}
