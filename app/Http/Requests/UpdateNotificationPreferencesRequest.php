<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateNotificationPreferencesRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'notify_by_email' => ['required', 'boolean'],
        ];
    }

    public function notifyByEmail(): bool
    {
        return $this->boolean('notify_by_email');
    }
}
