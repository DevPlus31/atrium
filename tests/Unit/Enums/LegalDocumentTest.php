<?php

declare(strict_types=1);

use App\Enums\LegalDocument;
use App\Modules\Data\LegalDocumentData;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

it('titles every document', function (LegalDocument $document): void {
    expect($document->title())->not->toBe('');
})->with(LegalDocument::cases());

it('finds the document in the current language', function (): void {
    App::setLocale('fr');

    expect(LegalDocument::Terms->path())->toBe(resource_path('legal/fr/terms.md'));
});

it('falls back to the fallback language when the document is not translated', function (): void {
    App::setLocale('de');

    expect(LegalDocument::Privacy->path())->toBe(resource_path('legal/en/privacy.md'));
});

it('fails loudly when no file exists, even in the fallback language', function (): void {
    App::setLocale('xx');
    config(['app.fallback_locale' => 'yy']);

    LegalDocument::Cookies->path();
})->throws(RuntimeException::class, 'The cookies document has no file in resources/legal for the [xx] locale or the fallback locale.');

it('strips raw HTML and unsafe links from the Markdown', function (): void {
    $directory = resource_path('legal/zz-test-'.getmypid());
    File::ensureDirectoryExists($directory);
    File::put($directory.'/terms.md', "Hello\n\n<script>alert(1)</script>\n\n[click](javascript:alert(1))");
    App::setLocale(basename($directory));

    try {
        $html = LegalDocumentData::fromDocument(LegalDocument::Terms, null)->html;
    } finally {
        File::deleteDirectory($directory);
    }

    expect($html)->toContain('<p>Hello</p>')
        ->not->toContain('<script>')
        ->not->toContain('javascript:');
});
