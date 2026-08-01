<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;

final class ProductAlreadyPublished extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Product [%s] is already published.', $id));
    }
}
