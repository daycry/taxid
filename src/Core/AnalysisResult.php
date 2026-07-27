<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core;

use Daycry\TaxId\DTO\ValidationResult;
use Daycry\TaxId\Registry\TaxIdScheme;

/**
 * Everything one pass of the validation pipeline learned: the verdict, the
 * detection it was based on, and — when the value is valid — the scheme that
 * actually accepted it.
 *
 * {@see Validator::validate()} throws away all but the verdict;
 * {@see Parser} needs the rest. Returning both from one call is what keeps the
 * parser from re-running detection and, worse, from re-deriving the winning
 * scheme with slightly different logic.
 */
final readonly class AnalysisResult
{
    public function __construct(
        public ValidationResult $validation,
        public DetectionResult $detection,
        public ?TaxIdScheme $scheme = null,
    ) {
    }

    public function isValid(): bool
    {
        return $this->validation->isValid();
    }
}
