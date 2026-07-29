<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Slovakia — rodné číslo, the birth number that identifies a natural person.
 *
 * The same Czechoslovak standard Czechia kept, so the arithmetic is shared with
 * {@see CzDicValidator} rather than copied — exactly as the eight-digit IČO
 * already is. Two forms exist and the length tells them apart:
 *
 * - **nine digits** — issued before 1954, when no check digit existed. Only the
 *   encoded date can be examined, so a typo in the serial is undetectable.
 * - **ten digits** — the modern form, where the tenth digit is the first nine
 *   modulo eleven, with a remainder of ten recorded as `0` for 1954-1985 births.
 *
 * This is a different number from the ten-digit DIČ that {@see SkVatValidator}
 * checks, and the two overlap at ten digits. That is not an error: the Validator
 * tries every scheme whose shape matches and accepts the value if any of them
 * does. `vat` is declared first, so a value that is a valid DIČ is still read as
 * one.
 */
final class SkRodneCisloValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['SK'];
    protected const array SCHEMES   = ['rodne_cislo'];

    public function verify(string $compact): bool
    {
        return match (strlen($compact)) {
            9       => CzDicValidator::isPlausibleBirthNumber($compact),
            10      => CzDicValidator::isValidBirthNumber($compact),
            default => false,
        };
    }
}
