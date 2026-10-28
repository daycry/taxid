<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Moldova — IDNO and IDNP share a digit-weighted mod-10 checksum.
 * Format: https://www.legis.md/cautare/downloadpdf/119470
 * Arithmetic reference: https://github.com/iAsig/idnx-validator
 */
final class MdIdnValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['MD'];
    protected const array SCHEMES = ['idno', 'idnp'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 13 || preg_match('/^(?:[12]\d{12}|09\d{11})$/', $compact) !== 1) {
            return false;
        }

        return Weights::sum(substr($compact, 0, 12), [7, 3, 1, 7, 3, 1, 7, 3, 1, 7, 3, 1]) % 10
            === (int) $compact[12];
    }
}
