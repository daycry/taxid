<?php

declare(strict_types=1);

namespace Daycry\TaxId\DTO;

/**
 * The outcome of validating a tax ID: a boolean plus the reasons behind it.
 *
 * Produced by every lenient entry point; those never throw, so this object is
 * the only channel through which a failure is reported.
 */
final readonly class ValidationResult
{
    /**
     * @param Violation[] $violations
     */
    public function __construct(
        public bool $valid,
        public array $violations,
    ) {
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @return Violation[]
     */
    public function violations(): array
    {
        return $this->violations;
    }

    public function firstViolation(): ?Violation
    {
        return $this->violations[0] ?? null;
    }

    /**
     * The first violation's human-readable message, or `null` when valid.
     *
     * Convenience for the common `$result->isValid() ? … : $result->error()`
     * shape, so callers don't have to null-chain through `firstViolation()`.
     */
    public function error(): ?string
    {
        return $this->firstViolation()?->message;
    }

    /**
     * The first violation's machine-readable code as a string, or `null` when valid.
     */
    public function errorCode(): ?string
    {
        return $this->firstViolation()?->code->value;
    }
}
