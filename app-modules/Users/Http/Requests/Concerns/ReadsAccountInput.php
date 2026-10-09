<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests\Concerns;

/**
 * Typed reads of the validated account fields the Users forms share. Each
 * getter is only called by requests whose rules validate that field.
 */
trait ReadsAccountInput
{
    public function name(): string
    {
        /** @var string $name */
        $name = $this->validated('name');

        return $name;
    }

    public function email(): string
    {
        /** @var string $email */
        $email = $this->validated('email');

        return $email;
    }

    public function password(): string
    {
        /** @var string $password */
        $password = $this->validated('password');

        return $password;
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
