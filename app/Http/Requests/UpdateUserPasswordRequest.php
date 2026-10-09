<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\AccountRules;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateUserPasswordRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => AccountRules::password(),
        ];
    }
}
