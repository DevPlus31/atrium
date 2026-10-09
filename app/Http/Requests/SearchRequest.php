<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SearchRequest extends FormRequest
{
    public const int MIN_LENGTH = 2;

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:'.self::MIN_LENGTH, 'max:100'],
        ];
    }

    public function term(): string
    {
        return mb_trim($this->string('q')->value());
    }
}
