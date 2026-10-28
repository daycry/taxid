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
     * @param bool|null $checksumVerified True when the complete checksum was verified;
     *                                   false for accepted structural-only/exceptional values;
     *                                   null for failures or unspecified metadata.
     * @param string|null $scheme The scheme that accepted the value, or null without a successful engine verdict.
     */
    public function __construct(
        public bool $valid,
        public array $violations,
        public ?bool $checksumVerified = null,
        public ?string $scheme = null,
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
