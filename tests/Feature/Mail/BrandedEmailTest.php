<?php

declare(strict_types=1);

use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Mail\Markdown;
use Tests\Fixtures\Notifications\GreetingNotification;

function renderEmail(User $user): string
{
    return (string) new GreetingNotification(url: 'https://atrium.test/orders')->toMail($user)->render();
}

it('shows the app name, and the uploaded logo once there is one', function (): void {
    $user = User::factory()->create();

    expect(renderEmail($user))->toContain(e((string) config('app.name')))
        ->not->toContain('notification-logo');

    $settings = resolve(GeneralSettings::class);
    $settings->logo_path = 'branding/logo.png';
    $settings->save();

    expect(renderEmail($user))->toContain('/storage/branding/logo.png');
});

it('points people to the support address when one is set', function (): void {
    $user = User::factory()->create();

    expect(renderEmail($user))->not->toContain('Need help?');

    $settings = resolve(GeneralSettings::class);
    $settings->support_email = 'help@example.com';
    $settings->save();

    expect(renderEmail($user))->toContain('Need help? Contact')
        ->toContain('mailto:help@example.com');
});

it('adapts to dark mode in mail apps that support it', function (): void {
    expect(renderEmail(User::factory()->create()))
        ->toContain('content="light dark"')
        ->toContain('prefers-color-scheme: dark');
});

it('writes the plain-text version with the same footer', function (): void {
    $settings = resolve(GeneralSettings::class);
    $settings->support_email = 'help@example.com';
    $settings->save();

    $text = (string) resolve(Markdown::class)->renderText('notifications::email', [
        'level' => 'info',
        'greeting' => 'Hi',
        'introLines' => ['Body'],
        'outroLines' => [],
        'actionText' => null,
        'actionUrl' => null,
        'displayableActionUrl' => null,
    ]);

    expect($text)->toContain('Need help? Contact help@example.com')
        ->toContain('All rights reserved.');
});

it("speaks the recipient's language, Laravel's own lines included", function (): void {
    app()->setLocale('fr');

    expect(renderEmail(User::factory()->create()))
        ->toContain('Cordialement,')
        ->toContain('Tous droits réservés.');
});
