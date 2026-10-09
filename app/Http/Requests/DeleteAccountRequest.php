<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class DeleteAccountRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
        ];
    }

    /**
     * Say why up front, rather than failing in the action.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->user();

                if ($user instanceof User && $user->soleAdministrativeRole() !== null) {
                    $validator->errors()->add('password', __('You are the only administrator. Give someone else the role before deleting your account.'));
                }
            },
        ];
    }
}
