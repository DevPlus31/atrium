<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use App\Models\User;
use App\Rules\ValidEmail;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->routedUser()) ?? false;
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'max:255',
                'email',
                new ValidEmail,
                Rule::unique(User::class)->ignore($this->routedUser()),
            ],
            'roles' => ['array', $this->preventSelfLockout()],
            'roles.*' => ['string', Rule::exists(Role::class, 'name')],
        ];
    }

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        /** @var list<string> $roles */
        $roles = $this->validated('roles', []);

        return $roles;
    }

    /**
     * Every user reaching this route holds the admin role (panel-entry
     * gate), so removing it from oneself would be an immediate lock-out.
     */
    private function preventSelfLockout(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! $this->user()?->is($this->routedUser())) {
                return;
            }

            $roles = is_array($value) ? $value : [];

            if (! in_array('admin', $roles, true)) {
                $fail(__('You cannot remove your own admin role.'));
            }
        };
    }

    private function routedUser(): User
    {
        $user = $this->route('user');

        assert($user instanceof User);

        return $user;
    }
}
