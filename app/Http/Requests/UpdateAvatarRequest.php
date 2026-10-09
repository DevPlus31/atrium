<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

final class UpdateAvatarRequest extends FormRequest
{
    /**
     * The largest photo accepted, in kilobytes.
     */
    public const int MAX_KILOBYTES = 4096;

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(self::MAX_KILOBYTES)
                    ->dimensions(Rule::dimensions()->minWidth(64)->minHeight(64)),
            ],
        ];
    }

    public function photo(): UploadedFile
    {
        $photo = $this->file('avatar');

        assert($photo instanceof UploadedFile);

        return $photo;
    }
}
