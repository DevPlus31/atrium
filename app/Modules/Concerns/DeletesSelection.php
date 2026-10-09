<?php

declare(strict_types=1);

namespace App\Modules\Concerns;

use App\Actions\DeleteEach;
use App\Modules\Toast;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

/**
 * For a bulk-delete controller: deletes the selection its request
 * (ValidatesBulkSelection) permitted, one by one and all or none, then
 * toasts how many went and how many selected rows were left out.
 */
trait DeletesSelection
{
    /**
     * @template TRecord of Model
     *
     * @param  Collection<int, TRecord>  $records  the permitted selection
     * @param  Closure(TRecord): void  $deleteOne  the module's own delete action
     * @param  Closure(int): string  $deleted  the module's ":count x deleted." sentence
     */
    private function deleteSelection(FormRequest $request, Collection $records, Closure $deleteOne, Closure $deleted): void
    {
        abort_if($records->isEmpty(), 403);

        resolve(DeleteEach::class)->handle($records, $deleteOne);

        $skipped = count((array) $request->validated('ids')) - $records->count();
        $message = $deleted($records->count());

        Toast::success($skipped === 0
            ? $message
            : $message.' '.trans_choice(':count could not be deleted.|:count could not be deleted.', $skipped));
    }
}
