<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Contracts\CheckDigitValidatorInterface;

/**
 * Shared base for the bundled national check-digit validators.
 *
 * Exists for exactly one reason: `supports()` is identical boilerplate in every
 * one of them, and repeating it ~80 times is ~80 chances to typo a country code
 * and silently wire the wrong algorithm to a scheme. Subclasses declare two
 * constants and implement `verify()`.
 *
 * `COUNTRIES` is a list rather than a single code because a handful of
 * algorithms genuinely serve more than one jurisdiction — the United Kingdom's
 * VAT check also covers Northern Ireland's `XI` numbers, for instance.
 *
 * Validators are **stateless**: {@see \Daycry\TaxId\Core\CheckDigitValidatorFactory}
 * builds one instance per class and shares it.
 */
abstract class AbstractNationalValidator implements CheckDigitValidatorInterface
{
    /**
     * ISO 3166-1 alpha-2 codes this validator serves, uppercase.
     *
     * @var string[]
     */
    protected const array COUNTRIES = [];

    /**
     * Registry scheme codes this validator serves, lowercase.
     *
     * @var string[]
     */
    protected const array SCHEMES = [];

    public function supports(string $countryCode, string $scheme): bool
    {
        return in_array(strtoupper($countryCode), static::COUNTRIES, true)
            && in_array(strtolower($scheme), static::SCHEMES, true);
    }

    /**
     * Digit at `$position` of `$value`, counted from the left. Convenience for
     * the many algorithms written as "d1 * w1 + d2 * w2 + …" in their official
     * description, so the code can read the same way.
     */
    final protected static function digitAt(string $value, int $position): int
    {
        return isset($value[$position]) ? (int) $value[$position] : 0;
    }
}
