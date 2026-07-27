<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core;

use Daycry\TaxId\Contracts\CheckDigitValidatorInterface;
use Daycry\TaxId\Exceptions\TaxIdException;

/**
 * Turns the registry's `check_digit` class-strings into validator instances,
 * one per class, reused for the lifetime of the factory.
 *
 * The registry stores class names rather than objects so that loading the
 * rules for ~90 countries doesn't instantiate a couple of hundred validators
 * up front; only the schemes actually exercised get built. Validators are
 * required to be stateless, which is what makes sharing one instance safe.
 */
final class CheckDigitValidatorFactory
{
    /** @var array<string, CheckDigitValidatorInterface> */
    private array $instances = [];

    /**
     * @param class-string<CheckDigitValidatorInterface> $class
     *
     * @throws TaxIdException When the class is missing or doesn't implement the contract —
     *                        i.e. when the registry data is wrong, which is a bug, not bad input.
     */
    public function make(string $class): CheckDigitValidatorInterface
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        if (! class_exists($class)) {
            throw new TaxIdException(sprintf('Check-digit validator "%s" does not exist.', $class));
        }

        if (! is_subclass_of($class, CheckDigitValidatorInterface::class)) {
            throw new TaxIdException(sprintf(
                'Check-digit validator "%s" must implement %s.',
                $class,
                CheckDigitValidatorInterface::class,
            ));
        }

        /** @var CheckDigitValidatorInterface $instance */
        $instance = new $class();

        return $this->instances[$class] = $instance;
    }
}
