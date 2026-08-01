<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use RuntimeException;

/**
 * Base type for business-rule violations raised by a module's domain layer.
 * Catch this to handle any domain failure regardless of the concrete module.
 */
abstract class DomainException extends RuntimeException
{
    //
}
