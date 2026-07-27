<?php

declare(strict_types=1);

namespace Daycry\TaxId\Contracts;

use Daycry\TaxId\DTO\ParsedTaxId;
use Daycry\TaxId\DTO\ValidationResult;

/**
 * Validator contract: "could this tax ID have been issued?".
 *
 * Implementations MUST never throw, for any input — violations are reported
 * through the returned {@see ValidationResult}.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §5
 */
interface ValidatorInterface
{
    /**
     * Validate a tax ID: structure first, then the scheme's check digit.
     *
     * @param string|ParsedTaxId $taxId   The value to validate.
     * @param string|null        $country ISO 3166-1 alpha-2. When `null`, inferred
     *                                    from a leading VAT prefix (`ESB12345678`).
     * @param string|null        $scheme  Require this specific registry scheme code
     *                                    (`nif`, `cif`, `cpf`…). When `null`, any
     *                                    scheme of the country is accepted.
     */
    public function validate(string|ParsedTaxId $taxId, ?string $country = null, ?string $scheme = null): ValidationResult;

    /**
     * Boolean shorthand for {@see validate()}. Never throws.
     */
    public function isValid(string|ParsedTaxId $taxId, ?string $country = null, ?string $scheme = null): bool;
}
