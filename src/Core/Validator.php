<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core;

use Daycry\TaxId\Contracts\ReservedValueDetectorInterface;
use Daycry\TaxId\Contracts\ValidatorInterface;
use Daycry\TaxId\DTO\ParsedTaxId;
use Daycry\TaxId\DTO\ValidationResult;
use Daycry\TaxId\DTO\Violation;
use Daycry\TaxId\Enums\ViolationCode;
use Daycry\TaxId\Registry\Registry;
use Daycry\TaxId\Registry\TaxIdScheme;

/**
 * The check-ordering pipeline: the heart of tax-ID validation.
 *
 * Normalizes the input, resolves its country and candidate schemes, then runs a
 * fixed sequence of checks ordered cheapest-and-most-specific first, returning
 * on the FIRST violation found:
 *
 *   1. `TooLong`           — raw input longer than any tax ID on earth (fail fast)
 *   2. `Blank`             — nothing left after normalization
 *   3. `TooShort`          — shorter than the shortest issued tax ID
 *   4. `IllegalCharacters` — something outside `A-Z0-9&` survived normalization
 *   5. `CountryRequired`   — no country given and no prefix to infer one from
 *   6. `UnknownCountry`    — that country has no rules in the registry
 *   7. `SchemeMismatch`    — a specific scheme was demanded; the value is a
 *                            valid *other* scheme of the same country
 *   8. `BadLength`         — no scheme matched, and the length fits none of the
 *                            country's schemes either
 *   9. `MalformedStructure`— no scheme matched, but the length was plausible
 *  10. `ReservedValue`     — well-formed, but in a documented never-issued range
 *  11. `CheckDigitFailed`  — the scheme's verifier rejected it
 *
 * **Several schemes can match one value**, and that is not an error: Germany's
 * eleven-digit IdNr and its ten-to-thirteen-digit Steuernummer overlap, as do a
 * US EIN and SSN. Steps 10 and 11 therefore run against *every* matching
 * scheme in registry order and the value is valid if **any** of them accepts
 * it; only when all fail is the first failure reported. Pass an explicit
 * `$scheme` when you need one specific reading enforced.
 *
 * A scheme with no registered check-digit algorithm passes step 11 silently:
 * plenty of jurisdictions publish a format and no verifier, and rejecting those
 * values would be inventing a rule that does not exist.
 *
 * `validate()` and `isValid()` NEVER throw, on any input.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §5
 */
final class Validator implements ValidatorInterface
{
    /**
     * Fail-fast ceiling on raw input, before any work is done. The longest
     * bundled scheme is 18 characters; 64 leaves generous room for separators
     * and prefixes while stopping a megabyte of junk from reaching the regexes.
     */
    public const int MAX_INPUT_LENGTH = 64;

    /** No jurisdiction issues a tax ID shorter than this. */
    public const int MIN_LENGTH = 4;

    /**
     * The character set a normalized tax ID may use.
     *
     * `&` is in there for Mexico: an RFC may legitimately contain an ampersand
     * (it comes from the company name), and it is the only punctuation any
     * bundled scheme allows.
     */
    private const string ILLEGAL_CHARACTERS = '/[^A-Z0-9&]/';

    private readonly SchemeDetector $detector;

    public function __construct(
        private readonly Registry $registry = new Registry(),
        private readonly Normalizer $normalizer = new Normalizer(),
        private readonly CheckDigitValidatorFactory $validators = new CheckDigitValidatorFactory(),
        ?SchemeDetector $detector = null,
    ) {
        $this->detector = $detector ?? new SchemeDetector($registry);
    }

    public function validate(string|ParsedTaxId $taxId, ?string $country = null, ?string $scheme = null): ValidationResult
    {
        return $this->analyze($taxId, $country, $scheme)->validation;
    }

    public function isValid(string|ParsedTaxId $taxId, ?string $country = null, ?string $scheme = null): bool
    {
        return $this->validate($taxId, $country, $scheme)->isValid();
    }

    /**
     * Run the full pipeline and keep everything it learned — the verdict, the
     * detection, and the scheme that accepted the value.
     *
     * {@see Parser} builds on this so parsing and validating can never disagree
     * about which scheme a value belongs to.
     */
    public function analyze(string|ParsedTaxId $taxId, ?string $country = null, ?string $scheme = null): AnalysisResult
    {
        if ($taxId instanceof ParsedTaxId) {
            $country ??= $taxId->countryCode;
            $taxId     = $taxId->compact;
        }

        if (strlen($taxId) > self::MAX_INPUT_LENGTH) {
            return $this->reject($taxId, ViolationCode::TooLong, 'The tax ID is too long.');
        }

        $normalized = $this->normalizer->normalize($taxId);

        if ($normalized === '') {
            return $this->reject($normalized, ViolationCode::Blank, 'The tax ID is empty.');
        }

        if (strlen($normalized) < self::MIN_LENGTH) {
            return $this->reject($normalized, ViolationCode::TooShort, 'The tax ID is too short.');
        }

        if (preg_match(self::ILLEGAL_CHARACTERS, $normalized) === 1) {
            return $this->reject($normalized, ViolationCode::IllegalCharacters, 'The tax ID contains illegal characters.');
        }

        $detection = $this->detector->detect($normalized, $country);

        if ($detection->countryCode === null) {
            return new AnalysisResult(
                $this->violation(ViolationCode::CountryRequired, 'No country was given and none could be inferred from the value.'),
                $detection,
            );
        }

        if (! $detection->countryKnown()) {
            return new AnalysisResult(
                $this->violation(
                    ViolationCode::UnknownCountry,
                    sprintf('Unknown or unsupported country code "%s".', $detection->countryCode),
                ),
                $detection,
            );
        }

        $candidates = $this->candidates($detection, $scheme);

        if ($candidates === []) {
            [$code, $message] = $this->explainNoMatch($detection, $scheme);

            return new AnalysisResult($this->violation($code, $message), $detection);
        }

        return $this->firstAccepting($detection, $candidates);
    }

    /**
     * Normalize + detect, without passing any verdict.
     */
    public function detect(string $taxId, ?string $country = null): DetectionResult
    {
        return $this->detector->detect($this->normalizer->normalize($taxId), $country);
    }

    public function registry(): Registry
    {
        return $this->registry;
    }

    public function detector(): SchemeDetector
    {
        return $this->detector;
    }

    public function normalizer(): Normalizer
    {
        return $this->normalizer;
    }

    /**
     * The schemes worth testing: the one the caller demanded, or every structural
     * match in registry order.
     *
     * @return TaxIdScheme[]
     */
    private function candidates(DetectionResult $detection, ?string $scheme): array
    {
        if ($scheme === null) {
            return $detection->matches;
        }

        $requested = $detection->scheme($scheme);

        return $requested === null ? [] : [$requested];
    }

    /**
     * Try each candidate; the value is valid as soon as one accepts it.
     *
     * When none does, the FIRST candidate's failure is reported — registry order
     * puts the most specific scheme first, so that is the most useful message.
     *
     * @param TaxIdScheme[] $candidates
     */
    private function firstAccepting(DetectionResult $detection, array $candidates): AnalysisResult
    {
        $firstFailure = null;

        foreach ($candidates as $candidate) {
            $failure = $this->rejectionFor($candidate, $detection->compact);

            if ($failure === null) {
                return new AnalysisResult(new ValidationResult(true, []), $detection, $candidate);
            }

            $firstFailure ??= $failure;
        }

        /** @var ValidationResult $firstFailure — the loop ran at least once. */
        return new AnalysisResult($firstFailure, $detection);
    }

    /**
     * Why this scheme rejects the value, or `null` when it accepts it.
     */
    private function rejectionFor(TaxIdScheme $scheme, string $compact): ?ValidationResult
    {
        $validator = $scheme->checkDigitClass === null
            ? null
            : $this->validators->make($scheme->checkDigitClass);

        if ($validator instanceof ReservedValueDetectorInterface && $validator->isReserved($compact)) {
            return $this->violation(
                ViolationCode::ReservedValue,
                'The tax ID is well-formed but falls in a range that is never issued.',
            );
        }

        if ($validator !== null && ! $validator->verify($compact)) {
            return $this->violation(ViolationCode::CheckDigitFailed, 'The tax ID check digit is invalid.');
        }

        return null;
    }

    /**
     * Pick the most informative violation for "nothing matched".
     *
     * Three genuinely different failures hide behind one symptom, and telling
     * them apart is most of the value a user gets from an error message:
     * asking for the wrong scheme, getting the length wrong, or getting the
     * shape wrong.
     *
     * @return array{0: ViolationCode, 1: string}
     */
    private function explainNoMatch(DetectionResult $detection, ?string $scheme): array
    {
        $profile = $detection->profile;

        if ($scheme !== null && $profile !== null && ! $profile->hasScheme($scheme)) {
            return [
                ViolationCode::SchemeMismatch,
                sprintf('Country "%s" has no tax ID scheme "%s".', $detection->countryCode, strtolower($scheme)),
            ];
        }

        if ($scheme !== null && $detection->hasAnyMatch()) {
            return [
                ViolationCode::SchemeMismatch,
                sprintf(
                    'The tax ID is a valid "%s", not the requested "%s".',
                    $detection->scheme()->code ?? 'unknown',
                    strtolower($scheme),
                ),
            ];
        }

        if ($profile !== null && ! in_array(strlen($detection->compact), $profile->lengths(), true)) {
            return [
                ViolationCode::BadLength,
                sprintf('The tax ID length is invalid for %s.', $profile->name),
            ];
        }

        return [
            ViolationCode::MalformedStructure,
            sprintf('The tax ID structure is not valid for %s.', $profile->name ?? $detection->countryCode),
        ];
    }

    /**
     * A failed analysis for input that never got as far as country detection.
     */
    private function reject(string $compact, ViolationCode $code, string $message): AnalysisResult
    {
        return new AnalysisResult(
            $this->violation($code, $message),
            new DetectionResult(null, null, $compact, []),
        );
    }

    private function violation(ViolationCode $code, string $message): ValidationResult
    {
        return new ValidationResult(false, [
            new Violation($code, 'taxid.violation.' . $code->value, $message),
        ]);
    }
}
