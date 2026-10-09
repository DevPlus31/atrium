<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        assert($user instanceof User);

        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            // Moving the email moves password resets with it, so it needs the
            // password: neither an impersonating admin nor someone at an
            // unlocked session can take the account over.
            'current_password' => [
                Rule::requiredIf(fn (): bool => $this->input('email') !== $user->email),
                'nullable',
                'string',
                'current_password',
            ],
        ];
    }

    /**
     * The validated profile fields, without the confirming password.
     *
     * @return array{name: string, email: string}
     */
    public function profile(): array
    {
        /** @var array{name: string, email: string} $profile */
        $profile = $this->safe()->only(['name', 'email']);

        return $profile;
    }
}
