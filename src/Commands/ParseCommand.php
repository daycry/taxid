<?php

declare(strict_types=1);

namespace Daycry\TaxId\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Daycry\TaxId\Config\Services;
use Daycry\TaxId\Enums\TaxIdFormat;
use Daycry\TaxId\TaxId as TaxIdService;

/**
 * `spark taxid:parse <taxid> [--country=XX] [--json]`
 *
 * Shows everything the package worked out about a value: country, scheme,
 * entity type, compact and printed forms, check digit, and the VAT number where
 * one applies. Exit code 1 when the value cannot be parsed.
 */
final class ParseCommand extends BaseCommand
{
    protected $group       = 'TaxId';
    protected $name        = 'taxid:parse';
    protected $description = 'Parses a tax ID and prints its country, scheme and canonical forms.';
    protected $usage       = 'taxid:parse <taxid> [--country=XX] [--json]';

    /** @var array<string, string> */
    protected $arguments = [
        'taxid' => 'The tax ID to parse.',
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
        $parsed  = $svc->tryParse($taxId, $country);

        if ($parsed === null) {
            $violation = $svc->validate($taxId, $country)->firstViolation();

            if (CLI::getOption('json')) {
                CommandSupport::writeJson([
                    'parsed'    => false,
                    'violation' => $violation === null ? null : [
                        'code'    => $violation->code->value,
                        'message' => $violation->message,
                    ],
                ]);
            } else {
                CLI::write('CANNOT PARSE: ' . $violation?->code->value . ' - ' . $violation?->message, 'red');
            }

            return EXIT_ERROR;
        }

        $data = [
            'parsed'      => true,
            'country'     => $parsed->countryCode,
            'scheme'      => $parsed->scheme,
            'scheme_name' => $parsed->schemeName,
            'entity_type' => $parsed->entityType->value,
            'vat'         => $parsed->vat,
            'vat_number'  => $parsed->vatNumber(),
            'compact'     => $parsed->compact,
            'print'       => $parsed->format(TaxIdFormat::Print),
            'anonymized'  => $parsed->format(TaxIdFormat::Anonymized),
            'check_digit' => $parsed->checkDigit,
        ];

        if (CLI::getOption('json')) {
            CommandSupport::writeJson($data);

            return EXIT_SUCCESS;
        }

        $rows = [];

        foreach ($data as $field => $value) {
            $rows[] = [$field, match (true) {
                is_bool($value) => $value ? 'yes' : 'no',
                $value === null => '-',
                default         => (string) $value,
            }];
        }

        CLI::table($rows, ['Field', 'Value']);

        return EXIT_SUCCESS;
    }
}
