<?php

declare(strict_types=1);

namespace Daycry\TaxId\Contracts;

/**
 * Optional, additive companion to {@see CheckDigitValidatorInterface}: knows
 * which structurally-perfect values a jurisdiction never issues.
 *
 * Two situations need this, and neither is expressible as a check-digit failure:
 *
 * - values whose check digit is mathematically fine but that are reserved or
 *   blocked — a Brazilian CPF of eleven identical digits satisfies the mod-11
 *   rule yet is not a CPF;
 * - schemes with **no** check digit at all whose ranges are still documented —
 *   a US SSN can never begin `000`, `666` or `9xx`.
 *
 * A validator implements this **in addition to** {@see CheckDigitValidatorInterface};
 * {@see \Daycry\TaxId\Core\Validator} consults it just before the check-digit
 * step and reports {@see \Daycry\TaxId\Enums\ViolationCode::ReservedValue}, so
 * the caller can tell "typo" from "not a real ID range".
 */
interface ReservedValueDetectorInterface
{
    /**
     * Whether the compact value falls in a documented never-issued range.
     * MUST never throw.
     */
    public function isReserved(string $compact): bool;
}
