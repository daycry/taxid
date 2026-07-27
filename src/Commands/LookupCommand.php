<?php

declare(strict_types=1);

namespace Daycry\TaxId\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Daycry\TaxId\Config\Services;
use Daycry\TaxId\TaxId as TaxIdService;

/**
 * `spark taxid:lookup <taxid> [--country=XX] [--json]`
 *
 * Validates, then asks the configured provider whether the taxpayer is actually
 * registered — for EU VAT numbers with `Config\TaxId::$provider = 'vies'`, that
 * is a live call to the European Commission.
 *
 * The three-state answer is preserved in the exit code, because collapsing it
 * would defeat the point:
 *
 * - **0** — valid, and registered (or nothing to check against).
 * - **1** — invalid tax ID.
 * - **2** — valid, but the provider says it is NOT registered.
 *
 * "Unknown" (no provider, or the service was unreachable) exits 0 with
 * `registered: null`: a VIES outage must not fail your build.
 */
final class LookupCommand extends BaseCommand
{
    protected $group       = 'TaxId';
    protected $name        = 'taxid:lookup';
    protected $description = 'Validates a tax ID and resolves the taxpayer through the configured provider (e.g. VIES).';
    protected $usage       = 'taxid:lookup <taxid> [--country=XX] [--json]';

    /** @var array<string, string> */
    protected $arguments = [
        'taxid' => 'The tax ID to look up.',
    ];

    /** @var array<string, string> */
    protected $options = [
        '--country' => 'ISO 3166-1 alpha-2 country code. Omit to infer it from a VAT prefix.',
        '--json'    => 'Emit the result as JSON instead of a table.',
    ];

    public function run(array $params): int
    {
        $taxId = $params[0] ?? CLI::prompt('Tax ID');

        /** @var TaxIdService $svc */
        $svc = service('taxid');

        $country = CommandSupport::stringOption('country') ?? Services::config()->defaultCountry;
        $result  = $svc->resolve($taxId, $country);

        $payload = [
            'valid'       => $result->isValid(),
            'country'     => $result->taxId?->countryCode,
            'scheme'      => $result->taxId?->scheme,
            'compact'     => $result->taxId?->compact,
            'vat_number'  => $result->taxId?->vatNumber(),
            'registered'  => $result->isRegistered(),
            'name'        => $result->name(),
            'address'     => $result->address(),
            'resolved_by' => $result->entity()?->resolvedBy,
            'violation'   => $result->validation()->firstViolation()?->code->value,
        ];

        if (CLI::getOption('json')) {
            CommandSupport::writeJson($payload);

            return self::exitCode($result->isValid(), $result->isRegistered());
        }

        if (! $result->isValid()) {
            CLI::write('INVALID: ' . $result->validation()->error(), 'red');

            return EXIT_ERROR;
        }

        CLI::write('VALID', 'green');
        CLI::write(match ($result->isRegistered()) {
            true    => CLI::color('REGISTERED', 'green'),
            false   => CLI::color('NOT REGISTERED', 'red'),
            default => CLI::color('REGISTRATION UNKNOWN (no provider could answer)', 'yellow'),
        });

        $rows = [];

        foreach ($payload as $field => $value) {
            $rows[] = [$field, match (true) {
                is_bool($value) => $value ? 'yes' : 'no',
                $value === null => '-',
                default         => (string) $value,
            }];
        }

        CLI::table($rows, ['Field', 'Value']);

        return self::exitCode(true, $result->isRegistered());
    }

    private static function exitCode(bool $valid, ?bool $registered): int
    {
        if (! $valid) {
            return EXIT_ERROR;
        }

        // 2 is reserved for "definitively not registered" so a script can tell it
        // apart from a malformed ID.
        return $registered === false ? 2 : EXIT_SUCCESS;
    }
}
