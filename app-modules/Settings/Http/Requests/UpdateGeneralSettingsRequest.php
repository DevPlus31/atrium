<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use App\Rules\ValidEmail;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateGeneralSettingsRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'support_email' => ['nullable', 'string', 'lowercase', 'max:255', 'email', new ValidEmail],
            'registration_open' => ['required', 'boolean'],
        ];
    }

    public function supportEmail(): ?string
    {
        $email = $this->validated('support_email');

        return is_string($email) && $email !== '' ? $email : null;
    }

    public function registrationOpen(): bool
    {
        return $this->boolean('registration_open');
    }
}
