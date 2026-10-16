<?php

declare(strict_types=1);

namespace App\Modules\Data;

use App\Enums\LegalDocument;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

/**
 * A legal page, rendered to HTML on the server from its Markdown file (raw
 * HTML in the file is stripped, so the result is safe to inject).
 */
final class LegalDocumentData extends Data
{
    public function __construct(
        public LegalDocument $document,
        public string $title,
        public string $html,
    ) {
        //
    }

    /**
     * The document in the current language. `:app` in the file becomes the
     * app's name and `:contact` the support address (Settings → General),
     * or the address mail is sent from.
     */
    public static function fromDocument(LegalDocument $document, ?string $supportEmail): self
    {
        $from = Config::get('mail.from.address');
        $markdown = strtr((string) file_get_contents($document->path()), [
            ':app' => Config::string('app.name'),
            ':contact' => $supportEmail ?? (is_string($from) ? $from : ''),
        ]);

        return new self(
            document: $document,
            title: $document->title(),
            html: Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
        );
    }
}
