<?php

declare(strict_types=1);

use App\Enums\ConsentCategory;

it('offers the configured categories in the configured order, skipping unknown ones', function (): void {
    config(['privacy.consent.categories' => ['marketing', 'unknown', 42, 'analytics']]);

    expect(ConsentCategory::offered())->toBe([ConsentCategory::Marketing, ConsentCategory::Analytics]);

    config(['privacy.consent.categories' => []]);

    expect(ConsentCategory::offered())->toBe([]);
});

it('labels and describes every category', function (ConsentCategory $category): void {
    expect($category->label())->not->toBe('')
        ->and($category->description())->not->toBe('');
})->with(ConsentCategory::cases());

it('counts only marketing as selling or sharing', function (): void {
    expect(ConsentCategory::Marketing->isSaleOrSharing())->toBeTrue()
        ->and(ConsentCategory::Analytics->isSaleOrSharing())->toBeFalse();
});
