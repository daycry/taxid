<?php

declare(strict_types=1);

namespace Daycry\TaxId\Enums;

/**
 * Output shapes for a tax ID.
 *
 * A pure enum (no backing value): unlike {@see ViolationCode} / {@see EntityType}
 * these cases are never persisted or serialized — they only select a rendering.
 * `Config\TaxId::$defaultFormat` maps its own string onto them at the CI4 layer.
 *
 * @see \Daycry\TaxId\Core\Formatter
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §4.1
 */
enum TaxIdFormat
{
    /** The canonical stored form: uppercase, no separators, no VAT prefix. `B12345678` */
    case Compact;

    /** The country's conventional human-facing mask. `123.456.789-09` */
    case Print;

    /** Everything but the last two characters replaced by `*`, for logs. `*********09` */
    case Anonymized;
}
