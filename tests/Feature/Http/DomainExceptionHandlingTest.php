<?php

declare(strict_types=1);

use App\Domain\Exceptions\LastAdministrator;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware('web')->post('/_test/broken-rule', fn () => throw LastAdministrator::forRole('admin'));
});

it('sends people back with a toast when a business rule breaks', function (): void {
    $this->from('/settings/profile')
        ->post('/_test/broken-rule')
        ->assertRedirect('/settings/profile')
        ->assertToast('That can’t be done any more: something changed in the meantime. Check and try again.', 'error');
});

it('answers API clients with a conflict and the rule', function (): void {
    $this->postJson('/_test/broken-rule')
        ->assertStatus(409)
        ->assertExactJson(['message' => 'The last holder of the [admin] role cannot delete their account.']);
});
