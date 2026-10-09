<?php

declare(strict_types=1);

namespace App\Modules\Data;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

/**
 * One hit of the global search: what to show and where it leads.
 */
final class SearchResultData extends Data
{
    public function __construct(
        public string $title,
        public ?string $description,
        public string $url,
    ) {
        //
    }

    /**
     * A hit on a record: its edit page when the viewer may update it, the
     * index filtered down to it otherwise.
     *
     * @param  string  $routes  the resource's route-name prefix, e.g. `admin.products`
     */
    public static function forRecord(User $viewer, Model $record, string $routes, string $title, ?string $description, string $filter): self
    {
        return new self(
            title: $title,
            description: $description,
            url: $viewer->can('update', $record)
                ? route($routes.'.edit', $record)
                : route($routes.'.index', ['filter' => ['search' => $filter]]),
        );
    }
}
