<?php

declare(strict_types=1);

namespace Daycry\TaxId\DTO;

use Daycry\TaxId\Enums\ViolationCode;

/**
 * A single reason a value failed validation.
 *
 * `$messageKey` is a stable dotted key (`taxid.violation.check_digit_failed`)
 * that a CI4 application can feed to `lang()` to translate the failure; the
 * framework-free core itself only ever produces the hardcoded English
 * `$message`. See docs/i18n.md.
 */
final readonly class Violation
{
    public function __construct(
        public ViolationCode $code,
        public string $messageKey,
        public string $message,
    ) {
    }
}
