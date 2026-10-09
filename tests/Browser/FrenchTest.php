<?php

declare(strict_types=1);

use App\Models\User;
use Tests\Fixtures\Notifications\GreetingNotification;

it('switches the interface to French', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($user);

    visit(route('appearance.edit'))
        ->assertSee('Language and region')
        ->click('#language')
        ->click('[role="option"]:has-text("Français")')
        ->assertSee('Langue et région')
        ->assertSee('Paramètres d’apparence')
        ->assertNoJavaScriptErrors();

    expect($user->refresh()->locale)->toBe('fr');
});

it('shows dates the French way', function (): void {
    $user = User::factory()->withoutTwoFactor()->create(['locale' => 'fr', 'timezone' => 'Europe/Paris']);
    $this->travelTo(now()->setDate(2026, 10, 8)->setTime(14, 5));
    $user->notify(new GreetingNotification());
    $this->actingAs($user);

    visit(route('notifications.index'))
        ->assertSee('1 non lue')
        ->assertSee('8 oct., 16:05')
        ->assertNoJavaScriptErrors();
});
