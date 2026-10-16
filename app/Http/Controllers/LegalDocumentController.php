<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LegalDocument;
use App\Modules\Data\LegalDocumentData;
use App\Settings\GeneralSettings;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public Terms of Service, Privacy Policy and Cookie Policy pages, in
 * the visitor's language.
 */
final readonly class LegalDocumentController
{
    public function __invoke(LegalDocument $document, GeneralSettings $settings): Response
    {
        return Inertia::render('legal-document/show', [
            'document' => LegalDocumentData::fromDocument($document, $settings->support_email),
        ]);
    }
}
