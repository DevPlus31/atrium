<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\AccountRules;
use Illuminate\Foundation\Http\FormRequest;

final class CreateUserPasswordRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => AccountRules::password(),
        ];
    }
}
