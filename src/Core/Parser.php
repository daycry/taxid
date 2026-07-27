<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core;

use Daycry\TaxId\Contracts\ParserInterface;
use Daycry\TaxId\DTO\ParsedTaxId;
use Daycry\TaxId\Enums\TaxIdFormat;
use Daycry\TaxId\Exceptions\InvalidTaxIdException;

/**
 * Turns a validated value into a {@see ParsedTaxId}, and renders it back out.
 *
 * Parsing is validation plus attribution: the {@see Validator} decides *whether*
 * the value is a tax ID, and this class records *which* one it is. Both facts
 * come out of a single {@see Validator::analyze()} call, so the two can never
 * disagree about which scheme won — which matters for the countries whose
 * schemes overlap.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §5
 */
final class Parser implements ParserInterface
{
    public function __construct(
        private readonly Validator $validator = new Validator(),
        private readonly Formatter $formatter = new Formatter(),
    ) {
    }

    /**
     * The compact form: uppercase, unseparated, and with a recognised country
     * or VAT prefix removed — but only when removing it yields a value the
     * country actually recognises (see {@see SchemeDetector}).
     *
     * Falls back to plain normalization for anything unrecognised, so this is
     * safe to call on user input of any quality.
     */
    public function normalize(string $taxId, ?string $country = null): string
    {
        return $this->validator->detect($taxId, $country)->compact;
    }

    /**
     * @throws InvalidTaxIdException When the value is not a valid tax ID; the
     *                               exception carries the full ValidationResult.
     */
    public function parse(string $taxId, ?string $country = null): ParsedTaxId
    {
        $analysis = $this->validator->analyze($taxId, $country);

        if (! $analysis->isValid() || $analysis->scheme === null || $analysis->detection->profile === null) {
            throw new InvalidTaxIdException($analysis->validation);
        }

        return $this->build($analysis, $taxId);
    }

    public function tryParse(string $taxId, ?string $country = null): ?ParsedTaxId
    {
        $analysis = $this->validator->analyze($taxId, $country);

        if (! $analysis->isValid() || $analysis->scheme === null || $analysis->detection->profile === null) {
            return null;
        }

        return $this->build($analysis, $taxId);
    }

    /**
     * Render a value. Unparseable input is normalized and returned as-is rather
     * than raising: callers format for display, often before validating.
     */
    public function format(string|ParsedTaxId $taxId, TaxIdFormat $f = TaxIdFormat::Print, ?string $country = null): string
    {
        if ($taxId instanceof ParsedTaxId) {
            return $taxId->format($f);
        }

        $parsed = $this->tryParse($taxId, $country);

        if ($parsed !== null) {
            return $parsed->format($f);
        }

        return $this->formatter->format($this->normalize($taxId, $country), null, $f);
    }

    public function validator(): Validator
    {
        return $this->validator;
    }

    /**
     * Assemble the DTO from an analysis already known to be valid.
     */
    private function build(AnalysisResult $analysis, string $input): ParsedTaxId
    {
        /** @var \Daycry\TaxId\Registry\TaxIdScheme $scheme — guaranteed by the caller. */
        $scheme = $analysis->scheme;

        /** @var \Daycry\TaxId\Registry\CountryProfile $profile — guaranteed by the caller. */
        $profile = $analysis->detection->profile;

        $compact = $analysis->detection->compact;

        return new ParsedTaxId(
            countryCode: $profile->countryCode,
            scheme: $scheme->code,
            schemeName: $scheme->name,
            entityType: $scheme->entityType,
            vat: $scheme->vat,
            compact: $compact,
            input: $input,
            checkDigit: $scheme->extractCheckDigit($compact),
            mask: $scheme->mask,
            vatPrefix: $profile->vatPrefix,
        );
    }
}
