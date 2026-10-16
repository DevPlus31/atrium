<?php

declare(strict_types=1);

use App\Models\ConsentRecord;

it('prunes expired personal data and reports the counts', function (): void {
    ConsentRecord::factory()->create(['created_at' => now()->subYears(5)]);

    $this->artisan('privacy:prune')
        ->expectsOutputToContain('consent records')
        ->assertSuccessful();

    expect(ConsentRecord::query()->exists())->toBeFalse();
});
