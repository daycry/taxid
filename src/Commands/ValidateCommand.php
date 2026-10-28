<?php

declare(strict_types=1);

namespace Daycry\TaxId\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Daycry\TaxId\Config\Services;
use Daycry\TaxId\TaxId as TaxIdService;

/**
 * `spark taxid:validate <taxid> [--country=XX] [--scheme=nif] [--json]`
 *
 * Thin wrapper over `service('taxid')->validate()`. The exit code mirrors the
 * verdict — 0 valid, 1 invalid — so it drops straight into a shell script or a
 * CI job.
 */
final class ValidateCommand extends BaseCommand
{
    protected $group       = 'TaxId';
    protected $name        = 'taxid:validate';
    protected $description = 'Validates a tax ID (structure and check digit). Exit code 0 when valid, 1 otherwise.';
    protected $usage       = 'taxid:validate <taxid> [--country=XX] [--scheme=<code>] [--json]';

    /** @var array<string, string> */
    protected $arguments = [
        'taxid' => 'The tax ID to validate.',
    ];

    /** @var array<string, string> */
    protected $options = [
        '--country' => 'ISO 3166-1 alpha-2 country code. Omit to infer it from a VAT prefix '
            . '(falls back to Config\\TaxId::$defaultCountry).',
        '--scheme'  => 'Require a specific scheme (nif, cif, cpf, …) instead of accepting any of the country\'s.',
        '--json'    => 'Emit the result as JSON instead of coloured CLI text.',
    ];

    public function run(array $params): int
    {
        $taxId = $params[0] ?? CLI::prompt('Tax ID');

        /** @var TaxIdService $svc */
        $svc = service('taxid');

        $country = CommandSupport::stringOption('country') ?? Services::config()->defaultCountry;
        $scheme  = CommandSupport::stringOption('scheme');

        $result    = $svc->validate($taxId, $country, $scheme);
        $violation = $result->firstViolation();
        $parsed    = $result->isValid() ? $svc->tryParse($taxId, $country) : null;
        $acceptedScheme = $parsed !== null && $result->scheme !== null
            ? $svc->scheme($parsed->countryCode, $result->scheme)
            : null;

        if (CLI::getOption('json')) {
            CommandSupport::writeJson([
                'valid'     => $result->isValid(),
                'country'   => $parsed?->countryCode,
                'scheme'    => $result->scheme,
                'checksum_verified' => $result->checksumVerified,
                'compact'   => $parsed?->compact,
                'violation' => $violation === null ? null : [
                    'code'    => $violation->code->value,
                    'message' => $violation->message,
                ],
            ]);

            return $result->isValid() ? EXIT_SUCCESS : EXIT_ERROR;
        }

        if ($result->isValid()) {
            CLI::write('VALID', 'green');
            CLI::write(sprintf('  %s / %s (%s)', $parsed?->countryCode, $result->scheme, $acceptedScheme?->name));
        } else {
            CLI::write('INVALID: ' . $violation?->code->value . ' - ' . $violation?->message, 'red');
        }

        return $result->isValid() ? EXIT_SUCCESS : EXIT_ERROR;
    }
}
