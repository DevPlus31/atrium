<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests\Concerns;

use App\Modules\Concerns\ReadsValidatedInput;

/**
 * Typed reads of the validated account fields the Users forms share. Each
 * getter is only called by requests whose rules validate that field.
 */
trait ReadsAccountInput
{
    use ReadsValidatedInput;

    public function name(): string
    {
        return $this->validatedString('name');
    }

    public function email(): string
    {
        return $this->validatedString('email');
    }

    public function password(): string
    {
        return $this->validatedString('password');
    }

    /**
     * The roles to grant; none when the form sent none.
     *
     * @return list<string>
     */
    public function roles(): array
    {
        /** @var list<string> $roles */
        $roles = $this->validated('roles', []);

        return $roles;
    }
}
