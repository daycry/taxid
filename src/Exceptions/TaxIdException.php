<?php

declare(strict_types=1);

namespace Daycry\TaxId\Exceptions;

use RuntimeException;

/**
 * Base class for every exception this package throws.
 *
 * Catch this to catch them all; it extends {@see RuntimeException} so an
 * application that only catches SPL exceptions still works.
 */
class TaxIdException extends RuntimeException
{
}
