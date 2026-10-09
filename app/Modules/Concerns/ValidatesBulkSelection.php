<?php

declare(strict_types=1);

namespace App\Modules\Concerns;

use App\Modules\IndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * For a bulk action on a data table's selected rows (`ids`, at most one
 * page): validates the selection and keeps the records the user may act on,
 * each checked against its policy. IDs not shaped like the model's keys are
 * skipped.
 */
trait ValidatesBulkSelection
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:'.IndexQuery::MAX_PER_PAGE],
            'ids.*' => ['required', 'string', 'distinct'],
        ];
    }

    /**
     * The selected records the user may apply the ability to.
     *
     * @template TRecord of Model
     *
     * @param  Builder<TRecord>  $query
     * @return Collection<int, TRecord>
     */
    public function permitted(Builder $query, string $ability): Collection
    {
        /** @var list<string> $ids */
        $ids = $this->validated('ids');
        $user = $this->user();

        // An ID of the wrong shape cannot exist, and PostgreSQL would reject
        // the whole query over it (a UUID column refuses "abc").
        $ids = array_filter($ids, self::keyShape($query->getModel()));

        $permitted = [];

        foreach ($query->whereKey($ids)->get() as $record) {
            if ($user?->can($ability, $record) === true) {
                $permitted[] = $record;
            }
        }

        return new Collection($permitted);
    }

    /**
     * How many selected rows were left out (gone, or not permitted).
     */
    public function skipped(int $permitted): int
    {
        return count((array) $this->validated('ids')) - $permitted;
    }

    /**
     * Whether a string has the shape of the model's keys.
     *
     * @return callable(string): bool
     */
    private static function keyShape(Model $model): callable
    {
        $traits = class_uses_recursive($model);

        return match (true) {
            $model->getKeyType() === 'int' => ctype_digit(...),
            in_array(HasUuids::class, $traits, true) => Str::isUuid(...),
            in_array(HasUlids::class, $traits, true) => Str::isUlid(...),
            default => static fn (string $id): bool => $id !== '',
        };
    }
}
