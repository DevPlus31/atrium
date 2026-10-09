<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use App\Models\User;
use App\Rules\GrantableRole;
use App\Rules\ValidEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Users\Http\Requests\Concerns\ReadsAccountInput;
use Spatie\Permission\Models\Role;

final class StoreUserRequest extends FormRequest
{
    use ReadsAccountInput;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
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
                Rule::unique(User::class),
            ],
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists(Role::class, 'name'), new GrantableRole($actor)],
        ];
    }
}
