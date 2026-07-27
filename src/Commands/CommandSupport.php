<?php

declare(strict_types=1);

namespace Daycry\TaxId\Commands;

use CodeIgniter\CLI\CLI;

/**
 * Two things every `taxid:*` command needs, in one place.
 *
 * {@see CLI::getOption()} returns `true` for a flag given without a value, so
 * `--country` on its own would otherwise flow into a string parameter as a
 * boolean. And `json_encode()` is typed `string|false`, which every command
 * would otherwise have to narrow on its own.
 *
 * @internal Not part of the public API.
 */
final class CommandSupport
{
    /**
     * An option's value, or `null` when it is absent or was passed as a bare
     * flag with no value.
     */
    public static function stringOption(string $name): ?string
    {
        $value = CLI::getOption($name);

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    /**
      * Accepts a map or a list: some commands emit an object, `taxid:countries`
      * emits an array of them.
      *
      * @param array<array-key, mixed> $data
      */
    public static function writeJson(array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        CLI::write($json === false ? '{}' : $json);
    }
}
