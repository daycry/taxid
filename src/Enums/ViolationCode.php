<?php

declare(strict_types=1);

namespace Daycry\TaxId\Enums;

/**
 * Every way a tax ID can fail validation, as a stable, machine-readable code.
 *
 * The backing values are part of the public API (they surface in JSON output
 * from the spark commands and in {@see \Daycry\TaxId\DTO\Violation::$code}):
 * do not rename or repurpose a case, only add new ones.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §4.1
 */
enum ViolationCode: string
{
    /** Input was empty, or became empty after normalization. */
    case Blank = 'blank';

    /** Shorter than the shortest tax ID any jurisdiction issues. */
    case TooShort = 'too_short';

    /** Longer than the longest tax ID any jurisdiction issues (fail-fast guard). */
    case TooLong = 'too_long';

    /** Characters outside `A-Z0-9` survived normalization. */
    case IllegalCharacters = 'illegal_characters';

    /** The country code is not in the registry. */
    case UnknownCountry = 'unknown_country';

    /** No country was supplied and none could be inferred from a VAT prefix. */
    case CountryRequired = 'country_required';

    /** Right shape, wrong length for every scheme of the country. */
    case BadLength = 'bad_length';

    /** No scheme of the country matches the value's structure. */
    case MalformedStructure = 'malformed_structure';

    /** The scheme's check-digit algorithm rejected the value. */
    case CheckDigitFailed = 'check_digit_failed';

    /**
     * The value is a valid tax ID for its country, but not of the scheme the
     * caller explicitly demanded (e.g. asked for `cif`, got a valid `nif`).
     */
    case SchemeMismatch = 'scheme_mismatch';

    /**
     * Structurally well-formed, but inside a documented never-issued range
     * (e.g. a US SSN starting `000`, or a Brazilian CPF of repeated digits).
     */
    case ReservedValue = 'reserved_value';

    /** Nothing was supplied to validate at all. */
    case NothingToValidate = 'nothing_to_validate';
}
