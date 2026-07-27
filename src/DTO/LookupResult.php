<?php

declare(strict_types=1);

namespace Daycry\TaxId\DTO;

/**
 * The result of `resolve()`: offline validity **and** whatever a provider could
 * add on top of it.
 *
 * The three-state `$registered` is the important part of this DTO:
 *
 * - `true`  — a provider confirmed the ID is registered with the tax authority.
 * - `false` — a provider actively answered "no such registration".
 * - `null`  — nobody could answer: no provider configured, the scheme is not
 *   VAT, or the remote service was unreachable. **Unknown is not "no".**
 *
 * With the default {@see \Daycry\TaxId\Providers\NullProvider} `$registered` is
 * always `null`, so a consuming application never mistakes "we didn't look" for
 * "it doesn't exist".
 *
 * `$taxId` is `null` only when the value failed validation — in that case
 * nothing was looked up at all and `$validation` holds the reason.
 */
final readonly class LookupResult
{
    public function __construct(
        public ?ParsedTaxId $taxId,
        public bool $valid,
        public ?bool $registered,
        public ?EntityInfo $entity,
        public ValidationResult $validation,
    ) {
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function isRegistered(): ?bool
    {
        return $this->registered;
    }

    public function taxId(): ?ParsedTaxId
    {
        return $this->taxId;
    }

    public function entity(): ?EntityInfo
    {
        return $this->entity;
    }

    /** The taxpayer's registered name, when a provider supplied one. */
    public function name(): ?string
    {
        return $this->entity?->name;
    }

    /** The taxpayer's registered address, when a provider supplied one. */
    public function address(): ?string
    {
        return $this->entity?->address;
    }

    public function validation(): ValidationResult
    {
        return $this->validation;
    }
}
