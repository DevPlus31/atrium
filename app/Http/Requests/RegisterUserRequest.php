<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\AccountRules;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterUserRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => AccountRules::name(),
            'email' => AccountRules::email(),
            'password' => AccountRules::password(),
        ];
    }
}
