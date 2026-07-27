<?php

declare(strict_types=1);

namespace Daycry\TaxId\Contracts;

use Daycry\TaxId\DTO\ParsedTaxId;
use Daycry\TaxId\Enums\TaxIdFormat;
use Daycry\TaxId\Exceptions\InvalidTaxIdException;

/**
 * Parser contract: normalization, parsing and formatting.
 *
 * `parse()` is the only strict member — everything else is lenient.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §5
 */
interface ParserInterface
{
    /**
     * Canonicalize a raw value: uppercase, strip separators, strip the VAT
     * country prefix. Does not validate, and never rejects characters.
     */
    public function normalize(string $taxId, ?string $country = null): string;

    /**
     * Parse a value into its recognised country/scheme.
     *
     * @throws InvalidTaxIdException When the value is not a valid tax ID; the
     *                               exception carries the full ValidationResult.
     */
    public function parse(string $taxId, ?string $country = null): ParsedTaxId;

    /**
     * Lenient {@see parse()}: `null` instead of an exception. Never throws.
     */
    public function tryParse(string $taxId, ?string $country = null): ?ParsedTaxId;

    /**
     * Render a value in the requested format.
     *
     * An unparseable value is returned in its normalized form rather than
     * raising — formatting is a display concern, not a validation gate.
     */
    public function format(string|ParsedTaxId $taxId, TaxIdFormat $f = TaxIdFormat::Print, ?string $country = null): string;
}
