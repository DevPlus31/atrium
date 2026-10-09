<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;

final class SkuAlreadyTaken extends DomainException
{
    public static function forSku(string $sku): self
    {
        return new self(sprintf('Another product already uses the SKU [%s].', $sku));
    }
}
