<?php

declare(strict_types=1);

namespace Daycry\TaxId\Registry;

use Daycry\TaxId\Contracts\CheckDigitValidatorInterface;
use Daycry\TaxId\Enums\EntityType;

/**
 * One tax-ID kind within a country — Spain's NIF, Brazil's CNPJ, a US EIN.
 *
 * A country has one or more of these, and their **order inside the country
 * profile is significant**: {@see \Daycry\TaxId\Core\SchemeDetector} reports
 * matches in registry order, so the most specific scheme must be declared
 * first (Spain: `nif`, then `nie`, then `cif`).
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §4.3
 * @see docs/adding-a-country.md
 */
final readonly class TaxIdScheme
{
    /**
     * @param string                                          $countryCode     ISO 3166-1 alpha-2, uppercase.
     * @param string                                          $code            Lowercase scheme code, unique within the country (`nif`, `cpf`, `vat`).
     * @param string                                          $name            Human-readable name, in the local language where that is the common usage.
     * @param EntityType                                      $entityType      Who the scheme identifies.
     * @param bool                                            $vat             Whether this doubles as the country's VAT number.
     * @param string                                          $pattern         Anchored PCRE applied to the compact form.
     * @param int[]                                           $lengths         Every legal compact length. Drives the BadLength violation.
     * @param string|null                                     $mask            Print mask: `#` consumes one value character, everything else is a literal.
     * @param class-string<CheckDigitValidatorInterface>|null $checkDigitClass The verifier, or `null` for a structural-only scheme.
     * @param array{0: int, 1: int}|null                      $checkDigitOffset `[offset, length]` of the check character(s) within the compact form; a negative offset counts from the end.
     * @param string[]                                        $examples        Valid values, exercised end-to-end by the data-integrity test.
     * @param string|null                                     $notes           Anything a consumer should know (issuing body, quirks, why there is no checksum).
     */
    public function __construct(
        public string $countryCode,
        public string $code,
        public string $name,
        public EntityType $entityType,
        public bool $vat,
        public string $pattern,
        public array $lengths,
        public ?string $mask = null,
        public ?string $checkDigitClass = null,
        public ?array $checkDigitOffset = null,
        public array $examples = [],
        public ?string $notes = null,
    ) {
    }

    /**
     * Whether a compact value has this scheme's shape. Structure only — the
     * check digit is a separate step.
     */
    public function matches(string $compact): bool
    {
        return preg_match($this->pattern, $compact) === 1;
    }

    public function hasCheckDigit(): bool
    {
        return $this->checkDigitClass !== null;
    }

    /**
     * The check character(s) of a compact value, when this scheme declares
     * where they live.
     */
    public function extractCheckDigit(string $compact): ?string
    {
        if ($this->checkDigitOffset === null) {
            return null;
        }

        [$offset, $length] = $this->checkDigitOffset;

        $extracted = substr($compact, $offset, $length);

        return $extracted === '' ? null : $extracted;
    }
}
