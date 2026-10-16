<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ConsentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreConsentRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'granted' => ['present', 'array', 'list'],
            'granted.*' => ['string', 'distinct', Rule::enum(ConsentCategory::class)->only(ConsentCategory::offered())],
        ];
    }

    /**
     * The categories the visitor allowed.
     *
     * @return list<ConsentCategory>
     */
    public function granted(): array
    {
        /** @var list<string> $granted */
        $granted = $this->validated('granted');

        return array_map(ConsentCategory::from(...), $granted);
    }

    /**
     * Whether the browser sends a Global Privacy Control signal.
     */
    public function globalPrivacyControl(): bool
    {
        return $this->header('Sec-GPC') === '1';
    }
}
