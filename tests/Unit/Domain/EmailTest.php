<?php

declare(strict_types=1);

use App\Domain\Exceptions\InvalidEmailException;
use App\Domain\ValueObjects\Email;

it('normalizes the address by trimming and lowercasing', function (): void {
    $email = new Email('  Jane.Doe@Example.COM  ');

    expect($email->value)->toBe('jane.doe@example.com')
        ->and((string) $email)->toBe('jane.doe@example.com');
});

it('rejects an invalid address', function (): void {
    expect(fn (): Email => new Email('not-an-email'))
        ->toThrow(InvalidEmailException::class);
});

it('treats addresses as equal when their normalized values match', function (): void {
    $email = new Email('jane@example.com');

    expect($email->equals(new Email('JANE@example.com')))->toBeTrue()
        ->and($email->equals(new Email('john@example.com')))->toBeFalse();
});
