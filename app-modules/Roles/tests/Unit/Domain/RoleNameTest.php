<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Roles\Domain\Exceptions\InvalidRoleName;
use Modules\Roles\Domain\ValueObjects\RoleName;

it('trims the name', function (): void {
    expect((string) new RoleName('  editor  '))->toBe('editor');
});

it('needs a name', function (): void {
    expect(fn (): RoleName => new RoleName('   '))->toThrow(InvalidRoleName::class, 'A role needs a name.');
});

it('knows the system roles', function (string $name, bool $system): void {
    expect(new RoleName($name)->isSystem())->toBe($system);
})->with([
    'panel role' => [User::PANEL_ROLE, true],
    'super-admin' => [User::SUPER_ADMIN_ROLE, true],
    'any other role' => ['editor', false],
]);

it('compares by value', function (): void {
    expect(new RoleName('editor')->equals(new RoleName(' editor ')))->toBeTrue()
        ->and(new RoleName('editor')->equals(new RoleName('viewer')))->toBeFalse();
});

it('lets only non-system roles change their name', function (string $from, string $to, bool $allowed): void {
    expect(new RoleName($from)->canBecome(new RoleName($to)))->toBe($allowed);
})->with([
    'any role renamed' => ['editor', 'author', true],
    'system role kept' => [User::PANEL_ROLE, User::PANEL_ROLE, true],
    'system role renamed' => [User::PANEL_ROLE, 'owners', false],
]);
