<?php

declare(strict_types=1);

namespace Daycry\TaxId\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * `spark taxid:publish [--force]`
 *
 * Publishes {@see \Daycry\TaxId\Config\TaxId} into the app as
 * `app/Config/TaxId.php` — the idiomatic CI4 "publish a config" pattern.
 *
 * The published class sits in `namespace Config` and **extends** the package's
 * config rather than copying it. Two things follow from that: CI4's
 * `config()`/`service('taxid')` resolution prefers the app's `Config\`
 * namespace and picks it up with no wiring, and any property the app doesn't
 * override keeps inheriting the package default — including properties added in
 * a later release. A hand-copied duplicate would silently drift.
 */
final class PublishCommand extends BaseCommand
{
    protected $group       = 'TaxId';
    protected $name        = 'taxid:publish';
    protected $description = 'Publish the TaxId config into the app (app/Config/TaxId.php) for customization.';
    protected $usage       = 'taxid:publish [--force]';

    /** @var array<string, string> */
    protected $options = [
        '--force' => 'Overwrite an existing app/Config/TaxId.php.',
    ];

    /**
     * Injection seam for the publish target.
     *
     * Production leaves this `null` and {@see run()} writes to
     * `APPPATH . 'Config/TaxId.php'`; tests point it at a temp file so the whole
     * flow — write, existing-file guard, `--force` overwrite — is exercised
     * without touching a real app's `Config/` directory.
     */
    public ?string $targetPath = null;

    public function run(array $params): int
    {
        $target = $this->targetPath ?? APPPATH . 'Config/TaxId.php';
        $force  = CLI::getOption('force') !== null || array_key_exists('force', $params);

        if (is_file($target) && ! $force) {
            CLI::error('app/Config/TaxId.php already exists — use --force to overwrite.');

            return EXIT_ERROR;
        }

        $content = $this->buildPublishedContent();

        if ($content === null) {
            return EXIT_ERROR;
        }

        $directory = dirname($target);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            CLI::error('Could not create directory: ' . $directory);

            return EXIT_ERROR;
        }

        if (file_put_contents($target, $content) === false) {
            CLI::error('Could not write to ' . $target);

            return EXIT_ERROR;
        }

        CLI::write('Published TaxId config to ' . clean_path($target), 'green');

        return EXIT_SUCCESS;
    }

    /**
     * Read the package's real `src/Config/TaxId.php` and rewrite exactly three
     * lines — the namespace, the `BaseConfig` import, and the class declaration
     * — so the published copy extends it. Everything else, docblocks included,
     * is kept verbatim.
     *
     * Each replacement is asserted to have matched. If the source layout ever
     * changes in a way that breaks one of them, this bails out rather than
     * writing a config that quietly no longer extends anything.
     */
    private function buildPublishedContent(): ?string
    {
        $source   = dirname(__DIR__) . '/Config/TaxId.php';
        $contents = file_get_contents($source);

        if ($contents === false) {
            CLI::error('Could not read the package config source at ' . $source);

            return null;
        }

        $replacements = [
            'namespace Daycry\TaxId\Config;'     => 'namespace Config;',
            'use CodeIgniter\Config\BaseConfig;' => 'use Daycry\TaxId\Config\TaxId as BaseTaxId;',
            'class TaxId extends BaseConfig'     => 'class TaxId extends BaseTaxId',
        ];

        foreach ($replacements as $search => $replace) {
            $count    = 0;
            $contents = str_replace($search, $replace, $contents, $count);

            if ($count === 0) {
                CLI::error(
                    'Unexpected source layout in src/Config/TaxId.php: could not find "' . $search . '". '
                    . 'Not publishing a possibly-broken config.',
                );

                return null;
            }
        }

        return $contents;
    }
}
