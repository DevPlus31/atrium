<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use App\Models\User;
use App\Rules\GrantableRole;
use App\Rules\ValidEmail;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Users\Http\Requests\Concerns\ReadsAccountInput;
use Spatie\Permission\Models\Role;

final class UpdateUserRequest extends FormRequest
{
    use ReadsAccountInput;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->routedUser()) ?? false;
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        $actor = $this->user();

        assert($actor instanceof User);

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
            'roles.*' => ['string', Rule::exists(Role::class, 'name'), new GrantableRole($actor, $this->currentRoles())],
        ];
    }

    /**
     * The submitted roles, or the user's current roles when the request
     * omits them — an update without roles never strips the account.
     *
     * @return list<string>
     */
    public function roles(): array
    {
        /** @var list<string>|null $roles */
        $roles = $this->validated('roles');

        return $roles ?? $this->currentRoles();
    }

    /**
     * @return list<string>
     */
    private function currentRoles(): array
    {
        /** @var list<string> $names */
        $names = $this->routedUser()->roles()->pluck('name')->all();

        return $names;
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

            if (! in_array(User::PANEL_ROLE, $roles, true)) {
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
