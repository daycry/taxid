<?php

declare(strict_types=1);

namespace Daycry\TaxId\Exceptions;

use Daycry\TaxId\DTO\ValidationResult;
use Throwable;

/**
 * Thrown by the strict entry points ({@see \Daycry\TaxId\Core\Parser::parse()}
 * and the facade's `parse()`) when a value cannot be parsed.
 *
 * Carries the full {@see ValidationResult} so a caller that catches it still
 * gets the machine-readable violation, not just a message string:
 *
 * ```php
 * try {
 *     $parsed = $taxid->parse($value, 'ES');
 * } catch (InvalidTaxIdException $e) {
 *     $code = $e->result()->firstViolation()?->code;   // ViolationCode::CheckDigitFailed
 * }
 * ```
 *
 * The lenient entry points (`validate()`, `isValid()`, `tryParse()`) never
 * throw this — they report through the result instead.
 */
final class InvalidTaxIdException extends TaxIdException
{
    public function __construct(
        private readonly ValidationResult $resultValue,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $firstViolation = $resultValue->firstViolation();
        $defaultMessage = $firstViolation !== null ? $firstViolation->message : 'Invalid tax ID';
        $finalMessage   = $message !== '' ? $message : $defaultMessage;

        parent::__construct($finalMessage, $code, $previous);
    }

    public function result(): ValidationResult
    {
        return $this->resultValue;
    }
}
