<?php

declare(strict_types=1);

namespace App\Actions;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final readonly class DeleteEach
{
    /**
     * Delete each record exactly as one by one (through the module's own
     * delete action, so its rules, logging and events apply), all or none.
     *
     * @template TRecord of Model
     *
     * @param  iterable<TRecord>  $records
     * @param  Closure(TRecord): void  $delete
     */
    public function handle(iterable $records, Closure $delete): void
    {
        DB::transaction(function () use ($records, $delete): void {
            foreach ($records as $record) {
                $delete($record);
            }
        });
    }
}
