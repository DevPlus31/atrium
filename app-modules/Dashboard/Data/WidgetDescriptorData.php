<?php

declare(strict_types=1);

namespace Modules\Dashboard\Data;

use Spatie\LaravelData\Data;

final class WidgetDescriptorData extends Data
{
    public function __construct(
        public string $key,
        public string $prop,
        public int $sort,
    ) {
        //
    }

    /**
     * Build a descriptor whose deferred prop name is free of dots, since
     * Inertia treats dotted prop names as nested paths.
     *
     * @param  array{key: string, sort: int}  $descriptor
     */
    public static function fromRegistry(array $descriptor): self
    {
        return new self(
            key: $descriptor['key'],
            prop: 'widget:'.str_replace('.', '_', $descriptor['key']),
            sort: $descriptor['sort'],
        );
    }
}
