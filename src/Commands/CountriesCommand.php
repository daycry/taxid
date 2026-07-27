<?php

declare(strict_types=1);

namespace Daycry\TaxId\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Daycry\TaxId\TaxId as TaxIdService;
use OutOfBoundsException;

/**
 * `spark taxid:countries [<country>] [--json]`
 *
 * Without an argument, lists every jurisdiction in the registry with its scheme
 * count. With one, prints that country's schemes in detail — code, name, entity
 * type, whether it is the VAT number, lengths, whether a check digit is
 * verified, and a worked example.
 *
 * The fastest way to answer "does this package cover X, and what does it call
 * the scheme I need?".
 */
final class CountriesCommand extends BaseCommand
{
    protected $group       = 'TaxId';
    protected $name        = 'taxid:countries';
    protected $description = 'Lists supported countries, or the tax ID schemes of one country.';
    protected $usage       = 'taxid:countries [<country>] [--json]';

    /** @var array<string, string> */
    protected $arguments = [
        'country' => 'Optional ISO 3166-1 alpha-2 code to detail.',
    ];

    /** @var array<string, string> */
    protected $options = [
        '--json' => 'Emit the listing as JSON instead of a table.',
    ];

    public function run(array $params): int
    {
        /** @var TaxIdService $svc */
        $svc = service('taxid');

        $country = $params[0] ?? null;

        return $country === null
            ? $this->listCountries($svc)
            : $this->detailCountry($svc, $country);
    }

    private function listCountries(TaxIdService $svc): int
    {
        $rows = [];

        foreach ($svc->countries() as $code) {
            $profile = $svc->country($code);
            $rows[]  = [
                $code,
                $profile->name,
                $profile->vatPrefix ?? '-',
                (string) count($profile->schemes),
                implode(', ', array_keys($profile->schemes)),
            ];
        }

        if (CLI::getOption('json')) {
            CommandSupport::writeJson(array_map(
                static fn (array $r): array => [
                    'country'    => $r[0],
                    'name'       => $r[1],
                    'vat_prefix' => $r[2] === '-' ? null : $r[2],
                    'schemes'    => explode(', ', $r[4]),
                ],
                $rows,
            ));

            return EXIT_SUCCESS;
        }

        CLI::table($rows, ['Code', 'Country', 'VAT prefix', '#', 'Schemes']);
        CLI::write(sprintf('%d countries, %d schemes.', count($rows), $svc->registry()->schemeCount()));

        return EXIT_SUCCESS;
    }

    private function detailCountry(TaxIdService $svc, string $country): int
    {
        try {
            $profile = $svc->country($country);
        } catch (OutOfBoundsException $e) {
            CLI::write($e->getMessage(), 'red');

            return EXIT_ERROR;
        }

        $rows = [];

        foreach ($profile->schemes as $scheme) {
            $rows[] = [
                $scheme->code,
                $scheme->name,
                $scheme->entityType->value,
                $scheme->vat ? 'yes' : 'no',
                implode('/', $scheme->lengths),
                $scheme->hasCheckDigit() ? 'yes' : 'no',
                $scheme->examples[0] ?? '-',
            ];
        }

        if (CLI::getOption('json')) {
            CommandSupport::writeJson([
                'country'    => $profile->countryCode,
                'name'       => $profile->name,
                'vat_prefix' => $profile->vatPrefix,
                'schemes'    => array_map(
                    static fn (array $r): array => [
                        'code'        => $r[0],
                        'name'        => $r[1],
                        'entity_type' => $r[2],
                        'vat'         => $r[3] === 'yes',
                        'lengths'     => array_map(intval(...), explode('/', $r[4])),
                        'check_digit' => $r[5] === 'yes',
                        'example'     => $r[6],
                    ],
                    $rows,
                ),
            ]);

            return EXIT_SUCCESS;
        }

        CLI::write($profile->countryCode . ' — ' . $profile->name, 'green');
        CLI::table($rows, ['Scheme', 'Name', 'Entity', 'VAT', 'Lengths', 'Checksum', 'Example']);

        return EXIT_SUCCESS;
    }
}
