<?php

declare(strict_types=1);

namespace Daycry\TaxId\Contracts;

/** Optional capability for schemes whose accepted variants do not all have a checksum. */
interface ChecksumAwareValidatorInterface extends CheckDigitValidatorInterface
{
    /** Whether this value has a complete, verifiable checksum. Called after verify(). */
    public function hasChecksum(string $compact): bool;
}
