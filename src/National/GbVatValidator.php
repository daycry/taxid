<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Contracts\ChecksumAwareValidatorInterface;
use Daycry\TaxId\Core\Checksum\Weights;

/**
 * United Kingdom — VAT registration number. Also serves Northern Ireland's `XI`
 * numbers, which are the same registrations under a different prefix since the
 * Protocol.
 *
 * Nine digits, optionally followed by a three-digit branch code that takes no
 * part in the check. Weights 8…2 over the first seven digits, plus the
 * two-digit check read as a number; the total must be a multiple of 97.
 *
 * There are two generations of the rule. When HMRC exhausted the original
 * series it introduced the "9755" variant, which adds 55 before the modulus.
 * A number is valid under **either**, and there is no way to tell from the
 * number itself which one it was issued under.
 *
 * Government departments (`GD` + 000–499) and health authorities (`HA` +
 * 500–999) have no checksum at all and are accepted on shape alone.
 */
final class GbVatValidator extends AbstractNationalValidator implements ChecksumAwareValidatorInterface
{
    protected const array COUNTRIES = ['GB', 'XI'];
    protected const array SCHEMES   = ['vat'];

    private const array WEIGHTS = [8, 7, 6, 5, 4, 3, 2];

    public function hasChecksum(string $compact): bool
    {
        return ctype_digit($compact);
    }

    public function verify(string $compact): bool
    {
        if (preg_match('/^(GD[0-4]\d{2}|HA[5-9]\d{2})$/', $compact) === 1) {
            return true;
        }

        $length = strlen($compact);

        if (($length !== 9 && $length !== 12) || ! ctype_digit($compact)) {
            return false;
        }

        // 0 is never a valid registration, and the whole-zero value would pass
        // the modulus test below.
        if (str_starts_with($compact, '0000000')) {
            return false;
        }

        $total = Weights::sum(substr($compact, 0, 7), self::WEIGHTS)
            + (int) substr($compact, 7, 2);

        return $total % 97 === 0 || ($total + 55) % 97 === 0;
    }
}
