<?php

declare(strict_types=1);

namespace Daycry\TaxId\DTO;

use Daycry\TaxId\Core\Formatter;
use Daycry\TaxId\Enums\EntityType;
use Daycry\TaxId\Enums\TaxIdFormat;

/**
 * A tax ID that has been recognised: which country, which scheme, and the
 * canonical form of the value.
 *
 * Only ever produced for a value that passed the full validation pipeline, so
 * every field is trustworthy. `$compact` is the canonical form (uppercase, no
 * separators, no VAT country prefix); `$input` preserves exactly what the
 * caller passed, so an application can echo the user's own formatting back.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §4.2
 */
final readonly class ParsedTaxId
{
    /**
     * @param string      $countryCode ISO 3166-1 alpha-2, e.g. `ES`.
     * @param string      $scheme      Registry scheme code, e.g. `nif`, `cif`, `cpf`.
     * @param string      $schemeName  Human-readable scheme name, e.g. `NIF — Número de Identificación Fiscal`.
     * @param EntityType  $entityType  Whether the scheme identifies a person, a company, or either.
     * @param bool        $vat         Whether this value doubles as the country's VAT number.
     * @param string      $compact     Canonical form: uppercase, unseparated, unprefixed.
     * @param string      $input       The caller's original, untouched input.
     * @param string|null $checkDigit  The verifier character(s), when the scheme declares their position.
     * @param string|null $mask        The scheme's print mask (`#` = one value character), when it has one.
     * @param string|null $vatPrefix   The country's VAT prefix (`ES`, `EL`, `XI`), when it has one.
     */
    public function __construct(
        public string $countryCode,
        public string $scheme,
        public string $schemeName,
        public EntityType $entityType,
        public bool $vat,
        public string $compact,
        public string $input,
        public ?string $checkDigit = null,
        public ?string $mask = null,
        public ?string $vatPrefix = null,
    ) {
    }

    public function format(TaxIdFormat $f = TaxIdFormat::Print): string
    {
        return (new Formatter())->format($this->compact, $this->mask, $f);
    }

    /**
     * The value as an internationally-quotable VAT number (`ESB12345678`).
     *
     * Returns `null` when the scheme is not a VAT scheme, or when the country
     * has no VAT prefix — a non-VAT tax ID must never be passed off as one.
     */
    public function vatNumber(): ?string
    {
        if (! $this->vat || $this->vatPrefix === null) {
            return null;
        }

        return $this->vatPrefix . $this->compact;
    }

    public function __toString(): string
    {
        return $this->compact;
    }
}
