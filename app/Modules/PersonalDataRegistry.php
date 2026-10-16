<?php

declare(strict_types=1);

namespace App\Modules;

use App\Models\User;
use Closure;
use InvalidArgumentException;

/**
 * Where each module says which personal data it holds about a user, so the
 * shell can hand it over (GDPR Art. 15 and 20, the CCPA "right to know") and
 * erase it with the account (GDPR Art. 17, the CCPA "right to delete").
 * Every source runs whether or not its module is switched on: switching a
 * module off does not remove what it stored.
 */
final class PersonalDataRegistry
{
    /**
     * @var array<string, array{module: string, exporter: (Closure(User): array<array-key, mixed>)|class-string, eraser: (Closure(User): void)|class-string|null}>
     */
    private array $sources = [];

    /**
     * Add a source of personal data under a unique key (`shop.orders`): an
     * exporter returning the user's records as JSON-ready arrays and,
     * optionally, an eraser deleting or anonymising them before the account
     * is deleted. Leave the eraser out for records the law obliges you to
     * keep (invoices, for instance), and say so in the privacy policy.
     *
     * @param  (Closure(User): array<array-key, mixed>)|class-string  $exporter
     * @param  (Closure(User): void)|class-string|null  $eraser
     */
    public function add(string $module, string $key, Closure|string $exporter, Closure|string|null $eraser = null): void
    {
        throw_if(array_key_exists($key, $this->sources), InvalidArgumentException::class, sprintf('Personal data source [%s] is already registered.', $key));

        $this->sources[$key] = [
            'module' => $module,
            'exporter' => $exporter,
            'eraser' => $eraser,
        ];
    }

    /**
     * Every source's records for the user, keyed and sorted by source key.
     *
     * @return array<string, array<array-key, mixed>>
     */
    public function export(User $user): array
    {
        $export = [];

        foreach ($this->sorted() as $key => $source) {
            $records = $this->callable($source['exporter'], $key)($user);

            throw_unless(is_array($records), InvalidArgumentException::class, sprintf('Personal data exporter [%s] must return an array.', $key));

            $export[$key] = $records;
        }

        return $export;
    }

    /**
     * Run every source's eraser for the user.
     */
    public function erase(User $user): void
    {
        foreach ($this->sorted() as $key => $source) {
            if ($source['eraser'] !== null) {
                $this->callable($source['eraser'], $key)($user);
            }
        }
    }

    /**
     * @return array<string, array{module: string, exporter: (Closure(User): array<array-key, mixed>)|class-string, eraser: (Closure(User): void)|class-string|null}>
     */
    private function sorted(): array
    {
        $sources = $this->sources;

        ksort($sources);

        return $sources;
    }

    /**
     * @param  Closure|class-string  $source
     */
    private function callable(Closure|string $source, string $key): callable
    {
        $callable = $source instanceof Closure ? $source : resolve($source);

        throw_unless(is_callable($callable), InvalidArgumentException::class, sprintf('Personal data source [%s] must be invokable.', $key));

        return $callable;
    }
}
