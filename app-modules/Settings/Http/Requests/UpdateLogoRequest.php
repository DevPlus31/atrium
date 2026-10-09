<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

final class UpdateLogoRequest extends FormRequest
{
    /**
     * The largest logo accepted, in kilobytes.
     */
    public const int MAX_KILOBYTES = 2048;

    /**
     * Raster formats only: an SVG served from the app's own origin can carry
     * script.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                File::image()
                    ->types(['png', 'jpg', 'jpeg', 'webp'])
                    ->max(self::MAX_KILOBYTES)
                    ->dimensions(Rule::dimensions()->minWidth(32)->minHeight(32)),
            ],
        ];
    }

    public function logo(): UploadedFile
    {
        $logo = $this->file('logo');

        assert($logo instanceof UploadedFile);

        return $logo;
    }
}
