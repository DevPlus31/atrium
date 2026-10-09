<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use App\Enums\AnnouncementLevel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAnnouncementRequest extends FormRequest
{
    public const int MAX_LENGTH = 280;

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:'.self::MAX_LENGTH],
            'level' => ['required', Rule::enum(AnnouncementLevel::class)],
            // An instant with its offset (the browser converts its local time).
            'ends_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function message(): string
    {
        return mb_trim($this->string('message')->value());
    }

    public function level(): AnnouncementLevel
    {
        return $this->enum('level', AnnouncementLevel::class) ?? AnnouncementLevel::Info;
    }

    public function endsAt(): ?CarbonImmutable
    {
        $endsAt = $this->validated('ends_at');

        return is_string($endsAt) && $endsAt !== '' ? CarbonImmutable::parse($endsAt)->utc() : null;
    }
}
