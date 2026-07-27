<?php

declare(strict_types=1);

use Daycry\TaxId\National\ArCuitValidator;
use Daycry\TaxId\National\AtUidValidator;
use Daycry\TaxId\National\AuAbnValidator;
use Daycry\TaxId\National\AuAcnValidator;
use Daycry\TaxId\National\AuTfnValidator;
use Daycry\TaxId\National\BeNationalNumberValidator;
use Daycry\TaxId\National\BeVatValidator;
use Daycry\TaxId\National\BgEgnValidator;
use Daycry\TaxId\National\BgEikValidator;
use Daycry\TaxId\National\BrCnpjValidator;
use Daycry\TaxId\National\BrCpfValidator;
use Daycry\TaxId\National\CaBnValidator;
use Daycry\TaxId\National\CaSinValidator;
use Daycry\TaxId\National\ChUidValidator;
use Daycry\TaxId\National\ClRutValidator;
use Daycry\TaxId\National\CnRicValidator;
use Daycry\TaxId\National\CnUsccValidator;
use Daycry\TaxId\National\CoNitValidator;
use Daycry\TaxId\National\CyVatValidator;
use Daycry\TaxId\National\CzDicValidator;
use Daycry\TaxId\National\DeIdNrValidator;
use Daycry\TaxId\National\DeVatValidator;
use Daycry\TaxId\National\DkCvrValidator;
use Daycry\TaxId\National\DoRncValidator;
use Daycry\TaxId\National\EcRucValidator;
use Daycry\TaxId\National\EeIsikukoodValidator;
use Daycry\TaxId\National\EeVatValidator;
use Daycry\TaxId\National\EsCifValidator;
use Daycry\TaxId\National\EsDniValidator;
use Daycry\TaxId\National\FiYtunnusValidator;
use Daycry\TaxId\National\FrSirenValidator;
use Daycry\TaxId\National\FrTvaValidator;
use Daycry\TaxId\National\GbVatValidator;
use Daycry\TaxId\National\GrAfmValidator;
use Daycry\TaxId\National\HrOibValidator;
use Daycry\TaxId\National\HuVatValidator;
use Daycry\TaxId\National\IeVatValidator;
use Daycry\TaxId\National\IlIdValidator;
use Daycry\TaxId\National\InGstinValidator;
use Daycry\TaxId\National\IsKennitalaValidator;
use Daycry\TaxId\National\ItCodiceFiscaleValidator;
use Daycry\TaxId\National\ItPartitaIvaValidator;
use Daycry\TaxId\National\JpCorporateNumberValidator;
use Daycry\TaxId\National\KrBrnValidator;
use Daycry\TaxId\National\LtPvmValidator;
use Daycry\TaxId\National\LuVatValidator;
use Daycry\TaxId\National\LvPersonalCodeValidator;
use Daycry\TaxId\National\LvVatValidator;
use Daycry\TaxId\National\MtVatValidator;
use Daycry\TaxId\National\MxRfcValidator;
use Daycry\TaxId\National\NlBsnValidator;
use Daycry\TaxId\National\NlBtwValidator;
use Daycry\TaxId\National\NoOrgnrValidator;
use Daycry\TaxId\National\NzIrdValidator;
use Daycry\TaxId\National\PeRucValidator;
use Daycry\TaxId\National\PlNipValidator;
use Daycry\TaxId\National\PlPeselValidator;
use Daycry\TaxId\National\PlRegonValidator;
use Daycry\TaxId\National\PtNifValidator;
use Daycry\TaxId\National\PyRucValidator;
use Daycry\TaxId\National\RoCnpValidator;
use Daycry\TaxId\National\RoCuiValidator;
use Daycry\TaxId\National\RsPibValidator;
use Daycry\TaxId\National\RuInnValidator;
use Daycry\TaxId\National\SeOrgnrValidator;
use Daycry\TaxId\National\SePersonnummerValidator;
use Daycry\TaxId\National\SeVatValidator;
use Daycry\TaxId\National\SiVatValidator;
use Daycry\TaxId\National\SkIcoValidator;
use Daycry\TaxId\National\SkVatValidator;
use Daycry\TaxId\National\TrTcknValidator;
use Daycry\TaxId\National\TrVknValidator;
use Daycry\TaxId\National\UsEinValidator;
use Daycry\TaxId\National\UsItinValidator;
use Daycry\TaxId\National\UsSsnValidator;
use Daycry\TaxId\National\UyRutValidator;
use Daycry\TaxId\National\VeRifValidator;

/**
 * Tax-ID rules, keyed by ISO 3166-1 alpha-2 country code.
 *
 * Hand-authored from publicly documented national formats and check-digit
 * algorithms — not copied from, nor derived from, any third-party dataset. See
 * docs/licensing.md for the rationale and docs/adding-a-country.md for how to
 * extend this file.
 *
 * Per country:
 *   name        English country name.
 *   vat_prefix  Prefix used when quoting the VAT number internationally, or
 *               null where the country has none. Note EL for Greece and XI for
 *               Northern Ireland — the two places it is not the country code.
 *   schemes     Ordered list; the FIRST structural match wins, so declare the
 *               most specific scheme first.
 *
 * Per scheme:
 *   code                Lowercase, unique within the country.
 *   name                Human-readable, in local usage where that is the norm.
 *   entity              natural | legal | both.
 *   vat                 Whether the value doubles as the VAT number.
 *   pattern             Anchored PCRE over the compact form.
 *   lengths             Every legal compact length; drives the BadLength violation.
 *   mask                Print mask, '#' = one value character. Optional.
 *   check_digit         Validator class, or absent for a structural-only scheme.
 *   check_digit_offset  [offset, length] of the check character(s); negative
 *                       offsets count from the end. Optional.
 *   examples            Valid values. The data-integrity test runs EVERY one of
 *                       them through the public facade, so a country cannot land
 *                       broken. They are synthetic unless noted: publishing real
 *                       taxpayers' identifiers would be a privacy problem, and a
 *                       checksum-valid synthetic value tests the same code path.
 *   notes               Anything a consumer should know — especially WHY a
 *                       scheme has no check-digit validator.
 *
 * @var array<string, array<string, mixed>>
 */
return [
    'AD' => [
        'name'       => 'Andorra',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'nrt',
                'name'     => 'NRT — Número de Registre Tributari',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^[ADEFGLOPU]\d{6}[A-Z]$/',
                'lengths'  => [8],
                'mask'     => '#-######-#',
                'examples' => ['A123456Z'],
                'notes'    => 'Leading letter encodes the taxpayer type. No public check-digit algorithm.',
            ],
        ],
    ],

    'AE' => [
        'name'       => 'United Arab Emirates',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'trn',
                'name'     => 'TRN — Tax Registration Number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{15}$/',
                'lengths'  => [15],
                'examples' => ['100000000000003'],
                'notes'    => 'The FTA publishes no check-digit algorithm.',
            ],
        ],
    ],

    'AL' => [
        'name'       => 'Albania',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'nipt',
                'name'     => 'NIPT — Numri i Identifikimit për Personin e Tatueshëm',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^[A-Z]\d{8}[A-Z]$/',
                'lengths'  => [10],
                'examples' => ['J91402501L'],
                'notes'    => 'First letter encodes the registration decade. No public check-digit algorithm.',
            ],
        ],
    ],

    'AM' => [
        'name'       => 'Armenia',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'tin',
                'name'     => 'ՀՎՀՀ — Taxpayer Identification Number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{8}$/',
                'lengths'  => [8],
                'examples' => ['12345678'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'AR' => [
        'name'       => 'Argentina',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'cuit',
                'name'               => 'CUIT/CUIL — Clave Única de Identificación Tributaria/Laboral',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^\d{11}$/',
                'lengths'            => [11],
                'mask'               => '##-########-#',
                'check_digit'        => ArCuitValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['20123456786'],
                'notes'              => 'CUIT for taxpayers, CUIL for employees; identical format and checksum.',
            ],
        ],
    ],

    'AT' => [
        'name'       => 'Austria',
        'vat_prefix' => 'AT',
        'schemes'    => [
            [
                'code'               => 'uid',
                'name'               => 'UID — Umsatzsteuer-Identifikationsnummer',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^U\d{8}$/',
                'lengths'            => [9],
                'check_digit'        => AtUidValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['U10223006'],
                'notes'              => 'The leading U belongs to the national number, not to the AT prefix.',
            ],
        ],
    ],

    'AU' => [
        'name'       => 'Australia',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'        => 'abn',
                'name'        => 'ABN — Australian Business Number',
                'entity'      => 'both',
                'vat'         => true,
                'pattern'     => '/^\d{11}$/',
                'lengths'     => [11],
                'mask'        => '## ### ### ###',
                'check_digit' => AuAbnValidator::class,
                'examples'    => ['51824753556'],
                'notes'       => 'Also the GST registration number.',
            ],
            [
                'code'               => 'acn',
                'name'               => 'ACN — Australian Company Number',
                'entity'             => 'legal',
                'vat'                => false,
                'pattern'            => '/^\d{9}$/',
                'lengths'            => [9],
                'mask'               => '### ### ###',
                'check_digit'        => AuAcnValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['004085616'],
            ],
            [
                'code'        => 'tfn',
                'name'        => 'TFN — Tax File Number',
                'entity'      => 'both',
                'vat'         => false,
                'pattern'     => '/^\d{8,9}$/',
                'lengths'     => [8, 9],
                'check_digit' => AuTfnValidator::class,
                'examples'    => ['123456782'],
                'notes'       => 'Highly restricted under Australian privacy law — avoid storing or logging it.',
            ],
        ],
    ],

    'AZ' => [
        'name'       => 'Azerbaijan',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'voen',
                'name'     => 'VÖEN — Taxpayer Identification Number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{10}$/',
                'lengths'  => [10],
                'examples' => ['1234567890'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'BA' => [
        'name'       => 'Bosnia and Herzegovina',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'jib',
                'name'     => 'JIB — Jedinstveni identifikacioni broj',
                'entity'   => 'legal',
                'vat'      => true,
                'pattern'  => '/^\d{13}$/',
                'lengths'  => [13],
                'examples' => ['1234567890123'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'BE' => [
        'name'       => 'Belgium',
        'vat_prefix' => 'BE',
        'schemes'    => [
            [
                'code'               => 'vat',
                'name'               => 'BTW/TVA — Ondernemingsnummer',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^[01]?\d{9}$/',
                'lengths'            => [9, 10],
                'mask'               => '####.###.###',
                'check_digit'        => BeVatValidator::class,
                'check_digit_offset' => [-2, 2],
                'examples'           => ['0776091951'],
            ],
            [
                'code'               => 'nn',
                'name'               => 'Rijksregisternummer / Numéro de registre national',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^\d{11}$/',
                'lengths'            => [11],
                'mask'               => '##.##.##-###.##',
                'check_digit'        => BeNationalNumberValidator::class,
                'check_digit_offset' => [-2, 2],
                'examples'           => ['85073003328'],
            ],
        ],
    ],

    'BG' => [
        'name'       => 'Bulgaria',
        'vat_prefix' => 'BG',
        'schemes'    => [
            [
                'code'               => 'egn',
                'name'               => 'ЕГН — Единен граждански номер',
                'entity'             => 'natural',
                'vat'                => true,
                'pattern'            => '/^\d{10}$/',
                'lengths'            => [10],
                'check_digit'        => BgEgnValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['7523169263'],
            ],
            [
                'code'               => 'eik',
                'name'               => 'ЕИК/BULSTAT — company number',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^(\d{9}|\d{13})$/',
                'lengths'            => [9, 13],
                'check_digit'        => BgEikValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['831902483', '8319024830010'],
            ],
        ],
    ],

    'BO' => [
        'name'       => 'Bolivia',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'nit',
                'name'     => 'NIT — Número de Identificación Tributaria',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{7,12}$/',
                'lengths'  => [7, 8, 9, 10, 11, 12],
                'examples' => ['1234567'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'BR' => [
        'name'       => 'Brazil',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'cnpj',
                'name'               => 'CNPJ — Cadastro Nacional da Pessoa Jurídica',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{14}$/',
                'lengths'            => [14],
                'mask'               => '##.###.###/####-##',
                'check_digit'        => BrCnpjValidator::class,
                'check_digit_offset' => [-2, 2],
                'examples'           => ['11222333000181'],
            ],
            [
                'code'               => 'cpf',
                'name'               => 'CPF — Cadastro de Pessoas Físicas',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^\d{11}$/',
                'lengths'            => [11],
                'mask'               => '###.###.###-##',
                'check_digit'        => BrCpfValidator::class,
                'check_digit_offset' => [-2, 2],
                'examples'           => ['11144477735'],
            ],
        ],
    ],

    'BY' => [
        'name'       => 'Belarus',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'unp',
                'name'     => 'УНП — Payer Account Number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{9}$/',
                'lengths'  => [9],
                'examples' => ['100003234'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'CA' => [
        'name'       => 'Canada',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'        => 'bn',
                'name'        => 'BN — Business Number (and GST/HST account)',
                'entity'      => 'legal',
                'vat'         => true,
                'pattern'     => '/^\d{9}([A-Z]{2}\d{4})?$/',
                'lengths'     => [9, 15],
                'check_digit' => CaBnValidator::class,
                'examples'    => ['123456782', '123456782RT0001'],
                'notes'       => 'The optional suffix is a CRA program account; only the 9-digit root is checksummed.',
            ],
            [
                'code'        => 'sin',
                'name'        => 'SIN — Social Insurance Number',
                'entity'      => 'natural',
                'vat'         => false,
                'pattern'     => '/^\d{9}$/',
                'lengths'     => [9],
                'mask'        => '### ### ###',
                'check_digit' => CaSinValidator::class,
                'examples'    => ['130692544'],
            ],
        ],
    ],

    'CH' => [
        'name'       => 'Switzerland',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'        => 'mwst',
                'name'        => 'MWST/TVA/IVA — VAT number',
                'entity'      => 'legal',
                'vat'         => true,
                'pattern'     => '/^CHE\d{9}(MWST|TVA|IVA)$/',
                'lengths'     => [16],
                'check_digit' => ChUidValidator::class,
                'examples'    => ['CHE116281839MWST'],
                'notes'       => 'The UID with the language-specific VAT suffix appended.',
            ],
            [
                'code'               => 'uid',
                'name'               => 'UID — Unternehmens-Identifikationsnummer',
                'entity'             => 'legal',
                'vat'                => false,
                'pattern'            => '/^CHE\d{9}$/',
                'lengths'            => [12],
                'mask'               => '###-###.###.###',
                'check_digit'        => ChUidValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['CHE116281839'],
                'notes'              => 'CHE is part of the number itself, not a country prefix.',
            ],
        ],
    ],

    'CL' => [
        'name'       => 'Chile',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'rut',
                'name'               => 'RUT/RUN — Rol Único Tributario',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^\d{7,8}[0-9K]$/',
                'lengths'            => [8, 9],
                'mask'               => '##.###.###-#',
                'check_digit'        => ClRutValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['123456785'],
                'notes'              => 'The verifier may be the letter K, standing for a check value of 10.',
            ],
        ],
    ],

    'CN' => [
        'name'       => 'China',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'uscc',
                'name'               => '统一社会信用代码 — Unified Social Credit Identifier',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^[0-9ABCDEFGHJKLMNPQRTUWXY]{18}$/',
                'lengths'            => [18],
                'check_digit'        => CnUsccValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['91350100M000100Y56'],
                'notes'              => 'Alphabet excludes I, O, S, V and Z to avoid misreading.',
            ],
            [
                'code'               => 'ric',
                'name'               => '居民身份证号码 — Resident identity card number',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^\d{17}[0-9X]$/',
                'lengths'            => [18],
                'check_digit'        => CnRicValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['110101199003070011'],
            ],
        ],
    ],

    'CO' => [
        'name'       => 'Colombia',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'nit',
                'name'               => 'NIT — Número de Identificación Tributaria',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^\d{9,11}$/',
                'lengths'            => [9, 10, 11],
                'check_digit'        => CoNitValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['8300013381'],
            ],
        ],
    ],

    'CR' => [
        'name'       => 'Costa Rica',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'cedula_juridica',
                'name'     => 'Cédula jurídica',
                'entity'   => 'legal',
                'vat'      => true,
                'pattern'  => '/^\d{10}$/',
                'lengths'  => [10],
                'examples' => ['3101123456'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'CY' => [
        'name'       => 'Cyprus',
        'vat_prefix' => 'CY',
        'schemes'    => [
            [
                'code'               => 'vat',
                'name'               => 'ΦΠΑ — VAT registration number',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^[0-9]\d{7}[A-Z]$/',
                'lengths'            => [9],
                'check_digit'        => CyVatValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['10259033P'],
            ],
        ],
    ],

    'CZ' => [
        'name'       => 'Czechia',
        'vat_prefix' => 'CZ',
        'schemes'    => [
            [
                'code'               => 'ico',
                'name'               => 'IČO — Identifikační číslo osoby',
                'entity'             => 'legal',
                'vat'                => false,
                'pattern'            => '/^\d{8}$/',
                'lengths'            => [8],
                'check_digit'        => CzDicValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['00006947'],
            ],
            [
                'code'        => 'dic',
                'name'        => 'DIČ — Daňové identifikační číslo',
                'entity'      => 'both',
                'vat'         => true,
                'pattern'     => '/^\d{8,10}$/',
                'lengths'     => [8, 9, 10],
                'check_digit' => CzDicValidator::class,
                'examples'    => ['00006947', '7103191238'],
                'notes'       => '8 digits = IČO; 9 or 10 = an individual rodné číslo.',
            ],
        ],
    ],

    'DE' => [
        'name'       => 'Germany',
        'vat_prefix' => 'DE',
        'schemes'    => [
            [
                'code'               => 'vat',
                'name'               => 'USt-IdNr — Umsatzsteuer-Identifikationsnummer',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^[1-9]\d{8}$/',
                'lengths'            => [9],
                'check_digit'        => DeVatValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['136695976'],
            ],
            [
                'code'               => 'idnr',
                'name'               => 'IdNr — Steuerliche Identifikationsnummer',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^[1-9]\d{10}$/',
                'lengths'            => [11],
                'check_digit'        => DeIdNrValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['12345678903'],
            ],
            [
                'code'     => 'steuernummer',
                'name'     => 'Steuernummer — local tax office number',
                'entity'   => 'both',
                'vat'      => false,
                'pattern'  => '/^\d{10,13}$/',
                'lengths'  => [10, 11, 12, 13],
                'examples' => ['1234567890'],
                'notes'    => 'Format and length vary by Land and there is no nationwide check digit. '
                    . 'Overlaps the IdNr at 11 digits: a value failing the IdNr checksum is still accepted here.',
            ],
        ],
    ],

    'DK' => [
        'name'       => 'Denmark',
        'vat_prefix' => 'DK',
        'schemes'    => [
            [
                'code'               => 'cvr',
                'name'               => 'CVR-nummer',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{8}$/',
                'lengths'            => [8],
                'mask'               => '## ## ## ##',
                'check_digit'        => DkCvrValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['13585628'],
            ],
            [
                'code'     => 'cpr',
                'name'     => 'CPR-nummer — personal number',
                'entity'   => 'natural',
                'vat'      => false,
                'pattern'  => '/^\d{10}$/',
                'lengths'  => [10],
                'mask'     => '######-####',
                'examples' => ['0101901234'],
                'notes'    => 'The modulus-11 check was abandoned in 2007, so no checksum is enforced.',
            ],
        ],
    ],

    'DO' => [
        'name'       => 'Dominican Republic',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'rnc',
                'name'               => 'RNC — Registro Nacional del Contribuyente',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{9}$/',
                'lengths'            => [9],
                'mask'               => '#-##-#####-#',
                'check_digit'        => DoRncValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['101010101'],
            ],
            [
                'code'     => 'cedula',
                'name'     => 'Cédula de identidad y electoral',
                'entity'   => 'natural',
                'vat'      => false,
                'pattern'  => '/^\d{11}$/',
                'lengths'  => [11],
                'examples' => ['00112345678'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'DZ' => [
        'name'       => 'Algeria',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'nif',
                'name'     => 'NIF — Numéro d\'Identification Fiscale',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{15}$/',
                'lengths'  => [15],
                'examples' => ['000216001808337'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'EC' => [
        'name'       => 'Ecuador',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'        => 'ruc',
                'name'        => 'RUC — Registro Único de Contribuyentes',
                'entity'      => 'both',
                'vat'         => true,
                'pattern'     => '/^\d{13}$/',
                'lengths'     => [13],
                'check_digit' => EcRucValidator::class,
                'examples'    => ['1710034065001', '1790012344001', '1760012320001'],
                'notes'       => 'Three algorithms selected by the third digit: natural person, public body, company.',
            ],
            [
                'code'               => 'cedula',
                'name'               => 'Cédula de identidad',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^\d{10}$/',
                'lengths'            => [10],
                'check_digit'        => EcRucValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['1710034065'],
            ],
        ],
    ],

    'EE' => [
        'name'       => 'Estonia',
        'vat_prefix' => 'EE',
        'schemes'    => [
            [
                'code'               => 'isikukood',
                'name'               => 'Isikukood — personal identification code',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^[1-6]\d{10}$/',
                'lengths'            => [11],
                'check_digit'        => EeIsikukoodValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['37605030299'],
            ],
            [
                'code'               => 'vat',
                'name'               => 'KMKR — VAT registration number',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{9}$/',
                'lengths'            => [9],
                'check_digit'        => EeVatValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['100594102'],
            ],
        ],
    ],

    'EG' => [
        'name'       => 'Egypt',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'tin',
                'name'     => 'Tax Registration Number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{9}$/',
                'lengths'  => [9],
                'mask'     => '###-###-###',
                'examples' => ['100531385'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'ES' => [
        'name'       => 'Spain',
        'vat_prefix' => 'ES',
        'schemes'    => [
            [
                'code'               => 'nif',
                'name'               => 'NIF — Número de Identificación Fiscal (personas físicas)',
                'entity'             => 'natural',
                'vat'                => true,
                'pattern'            => '/^\d{8}[A-Z]$/',
                'lengths'            => [9],
                'mask'               => '########-#',
                'check_digit'        => EsDniValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['12345678Z'],
            ],
            [
                'code'               => 'nie',
                'name'               => 'NIE — Número de Identidad de Extranjero',
                'entity'             => 'natural',
                'vat'                => true,
                'pattern'            => '/^[XYZ]\d{7}[A-Z]$/',
                'lengths'            => [9],
                'mask'               => '#-#######-#',
                'check_digit'        => EsDniValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['X1234567L', 'Y1234567X', 'Z1234567R'],
            ],
            [
                'code'               => 'cif',
                'name'               => 'CIF — Código de Identificación Fiscal (personas jurídicas)',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^[ABCDEFGHJKLMNPQRSUVW]\d{7}[0-9A-J]$/',
                'lengths'            => [9],
                'mask'               => '#-#######-#',
                'check_digit'        => EsCifValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['B12345674', 'A58818501'],
                'notes'              => 'Whether the control is a digit or a letter depends on the leading organisation letter.',
            ],
        ],
    ],

    'FI' => [
        'name'       => 'Finland',
        'vat_prefix' => 'FI',
        'schemes'    => [
            [
                'code'               => 'ytunnus',
                'name'               => 'Y-tunnus — business identity code',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{8}$/',
                'lengths'            => [8],
                'mask'               => '#######-#',
                'check_digit'        => FiYtunnusValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['02012566'],
            ],
        ],
    ],

    'FR' => [
        'name'       => 'France',
        'vat_prefix' => 'FR',
        'schemes'    => [
            [
                'code'               => 'siret',
                'name'               => 'SIRET — establishment number',
                'entity'             => 'legal',
                'vat'                => false,
                'pattern'            => '/^\d{14}$/',
                'lengths'            => [14],
                'mask'               => '### ### ### #####',
                'check_digit'        => FrSirenValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['55210055400013'],
            ],
            [
                'code'               => 'tva',
                'name'               => 'TVA intracommunautaire',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^[0-9A-Z]{2}\d{9}$/',
                'lengths'            => [11],
                'check_digit'        => FrTvaValidator::class,
                'check_digit_offset' => [0, 2],
                'examples'           => ['96552100554'],
                'notes'              => 'Numeric keys are derived from the SIREN; alphabetic keys are not publicly documented.',
            ],
            [
                'code'               => 'siren',
                'name'               => 'SIREN — legal entity number',
                'entity'             => 'legal',
                'vat'                => false,
                'pattern'            => '/^\d{9}$/',
                'lengths'            => [9],
                'mask'               => '### ### ###',
                'check_digit'        => FrSirenValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['552100554'],
            ],
        ],
    ],

    'GB' => [
        'name'       => 'United Kingdom',
        'vat_prefix' => 'GB',
        'schemes'    => [
            [
                'code'        => 'vat',
                'name'        => 'VAT registration number',
                'entity'      => 'legal',
                'vat'         => true,
                'pattern'     => '/^(\d{9}|\d{12}|GD[0-4]\d{2}|HA[5-9]\d{2})$/',
                'lengths'     => [5, 9, 12],
                'check_digit' => GbVatValidator::class,
                'examples'    => ['123456782', 'GD001', 'HA599'],
                'notes'       => 'Two modulus-97 generations coexist; GD/HA numbers have no checksum at all.',
            ],
            [
                'code'     => 'utr',
                'name'     => 'UTR — Unique Taxpayer Reference',
                'entity'   => 'both',
                'vat'      => false,
                'pattern'  => '/^\d{10}$/',
                'lengths'  => [10],
                'examples' => ['1234567890'],
                'notes'    => 'HMRC does not publish the check-digit algorithm, so only the shape is verified.',
            ],
            [
                'code'     => 'nino',
                'name'     => 'NINO — National Insurance number',
                'entity'   => 'natural',
                'vat'      => false,
                'pattern'  => '/^[A-CEGHJ-PR-TW-Z][A-CEGHJ-NPR-TW-Z]\d{6}[A-D]$/',
                'lengths'  => [9],
                'mask'     => '## ## ## ## #',
                'examples' => ['AB123456C'],
                'notes'    => 'No check digit; the excluded letters are what the pattern enforces.',
            ],
        ],
    ],

    'GE' => [
        'name'       => 'Georgia',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'tin',
                'name'     => 'Taxpayer Identification Number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{9}$/',
                'lengths'  => [9],
                'examples' => ['123456789'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'GR' => [
        'name'       => 'Greece',
        'vat_prefix' => 'EL',
        'schemes'    => [
            [
                'code'               => 'afm',
                'name'               => 'ΑΦΜ — Αριθμός Φορολογικού Μητρώου',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^\d{9}$/',
                'lengths'            => [9],
                'check_digit'        => GrAfmValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['094259216'],
                'notes'              => 'Quoted internationally with the prefix EL, not GR.',
            ],
        ],
    ],

    'GT' => [
        'name'       => 'Guatemala',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'nit',
                'name'     => 'NIT — Número de Identificación Tributaria',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{4,11}[0-9K]$/',
                'lengths'  => [5, 6, 7, 8, 9, 10, 11, 12],
                'examples' => ['12345678'],
                'notes'    => 'Checksum not implemented; only the shape is verified.',
            ],
        ],
    ],

    'HK' => [
        'name'       => 'Hong Kong',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'br',
                'name'     => 'Business Registration Number',
                'entity'   => 'legal',
                'vat'      => false,
                'pattern'  => '/^\d{8}$/',
                'lengths'  => [8],
                'examples' => ['12345678'],
                'notes'    => 'Hong Kong levies no VAT. No public check-digit algorithm.',
            ],
        ],
    ],

    'HN' => [
        'name'       => 'Honduras',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'rtn',
                'name'     => 'RTN — Registro Tributario Nacional',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{14}$/',
                'lengths'  => [14],
                'examples' => ['08019995123453'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'HR' => [
        'name'       => 'Croatia',
        'vat_prefix' => 'HR',
        'schemes'    => [
            [
                'code'               => 'oib',
                'name'               => 'OIB — Osobni identifikacijski broj',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^\d{11}$/',
                'lengths'            => [11],
                'check_digit'        => HrOibValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['69435151530'],
            ],
        ],
    ],

    'HU' => [
        'name'       => 'Hungary',
        'vat_prefix' => 'HU',
        'schemes'    => [
            [
                'code'               => 'adoszam',
                'name'               => 'Adószám — full tax number',
                'entity'             => 'legal',
                'vat'                => false,
                'pattern'            => '/^\d{11}$/',
                'lengths'            => [11],
                'mask'               => '########-#-##',
                'check_digit'        => HuVatValidator::class,
                'examples'           => ['10778757443'],
                'notes'              => 'Digits 9-11 are the VAT regime and county codes and carry no checksum.',
            ],
            [
                'code'               => 'anum',
                'name'               => 'Közösségi adószám — EU VAT number',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{8}$/',
                'lengths'            => [8],
                'check_digit'        => HuVatValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['10778757'],
            ],
        ],
    ],

    'ID' => [
        'name'       => 'Indonesia',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'npwp',
                'name'     => 'NPWP — Nomor Pokok Wajib Pajak',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{15,16}$/',
                'lengths'  => [15, 16],
                'examples' => ['013456789012000'],
                'notes'    => 'Migrating to a 16-digit form aligned with the national ID. No public checksum.',
            ],
        ],
    ],

    'IE' => [
        'name'       => 'Ireland',
        'vat_prefix' => 'IE',
        'schemes'    => [
            [
                'code'        => 'vat',
                'name'        => 'VAT registration number',
                'entity'      => 'both',
                'vat'         => true,
                'pattern'     => '/^(\d{7}[A-W][A-IW]|\d{7}[A-W]|\d[A-Z+*]\d{5}[A-W])$/',
                'lengths'     => [8, 9],
                'check_digit' => IeVatValidator::class,
                'examples'    => ['1234567T', '1234567FA'],
                'notes'       => 'Three coexisting formats: pre-2013, post-2013 and the old individual style.',
            ],
        ],
    ],

    'IL' => [
        'name'       => 'Israel',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'hp',
                'name'               => 'ח.פ. — company number / VAT dealer number',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^5\d{8}$/',
                'lengths'            => [9],
                'check_digit'        => IlIdValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['512345679'],
            ],
            [
                'code'               => 'id',
                'name'               => 'מספר זהות — personal identity number',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^\d{9}$/',
                'lengths'            => [9],
                'check_digit'        => IlIdValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['123456782'],
            ],
        ],
    ],

    'IN' => [
        'name'       => 'India',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'gstin',
                'name'               => 'GSTIN — Goods and Services Tax Identification Number',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^\d{2}[A-Z]{5}\d{4}[A-Z][0-9A-Z]Z[0-9A-Z]$/',
                'lengths'            => [15],
                'check_digit'        => InGstinValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['27AAPFU0939F1ZV'],
                'notes'              => 'Embeds the holder\'s PAN, so a PAN typo changes the check character.',
            ],
            [
                'code'     => 'pan',
                'name'     => 'PAN — Permanent Account Number',
                'entity'   => 'both',
                'vat'      => false,
                'pattern'  => '/^[A-Z]{5}\d{4}[A-Z]$/',
                'lengths'  => [10],
                'examples' => ['AAPFU0939F'],
                'notes'    => 'The final letter is a check character whose algorithm the Income Tax Department does not publish.',
            ],
        ],
    ],

    'IS' => [
        'name'       => 'Iceland',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'kennitala',
                'name'               => 'Kennitala — identification number',
                'entity'             => 'both',
                'vat'                => false,
                'pattern'            => '/^\d{10}$/',
                'lengths'            => [10],
                'mask'               => '######-####',
                'check_digit'        => IsKennitalaValidator::class,
                'check_digit_offset' => [8, 1],
                'examples'           => ['1203894599'],
                'notes'              => 'Companies add 40 to the day field. The last digit is the century, not a check.',
            ],
            [
                'code'     => 'vsk',
                'name'     => 'VSK — VAT registration number',
                'entity'   => 'legal',
                'vat'      => true,
                'pattern'  => '/^\d{5,6}$/',
                'lengths'  => [5, 6],
                'examples' => ['12345'],
                'notes'    => 'A sequential registration number with no check digit.',
            ],
        ],
    ],

    'IT' => [
        'name'       => 'Italy',
        'vat_prefix' => 'IT',
        'schemes'    => [
            [
                'code'               => 'cf',
                'name'               => 'Codice fiscale',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^[A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]$/',
                'lengths'            => [16],
                'check_digit'        => ItCodiceFiscaleValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['RSSMRA85T10A562S'],
                'notes'              => 'Derived from name, birth date and birthplace. Handles omocodia letter substitutions.',
            ],
            [
                'code'               => 'piva',
                'name'               => 'Partita IVA',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{11}$/',
                'lengths'            => [11],
                'check_digit'        => ItPartitaIvaValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['00743110157'],
            ],
        ],
    ],

    'JP' => [
        'name'       => 'Japan',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'houjinbangou',
                'name'               => '法人番号 — Corporate Number',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{13}$/',
                'lengths'            => [13],
                'check_digit'        => JpCorporateNumberValidator::class,
                'check_digit_offset' => [0, 1],
                'examples'           => ['3700150000123'],
                'notes'              => 'The check digit is the FIRST digit, not the last.',
            ],
            [
                'code'     => 'mynumber',
                'name'     => 'マイナンバー — Individual Number',
                'entity'   => 'natural',
                'vat'      => false,
                'pattern'  => '/^\d{12}$/',
                'lengths'  => [12],
                'examples' => ['123456789018'],
                'notes'    => 'Use is legally restricted in Japan. Structural check only — do not store it casually.',
            ],
        ],
    ],

    'KE' => [
        'name'       => 'Kenya',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'pin',
                'name'     => 'KRA PIN — Personal Identification Number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^[AP]\d{9}[A-Z]$/',
                'lengths'  => [11],
                'examples' => ['A123456789Z'],
                'notes'    => 'A = individual, P = company. No public check-digit algorithm.',
            ],
        ],
    ],

    'KR' => [
        'name'       => 'South Korea',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'brn',
                'name'               => '사업자등록번호 — Business Registration Number',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{10}$/',
                'lengths'            => [10],
                'mask'               => '###-##-#####',
                'check_digit'        => KrBrnValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['1234567891'],
            ],
        ],
    ],

    'KZ' => [
        'name'       => 'Kazakhstan',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'bin',
                'name'     => 'BIN/IIN — business or individual identification number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{12}$/',
                'lengths'  => [12],
                'examples' => ['123456789012'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'LI' => [
        'name'       => 'Liechtenstein',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'uid',
                'name'     => 'MWST-Nummer',
                'entity'   => 'legal',
                'vat'      => true,
                'pattern'  => '/^\d{5}$/',
                'lengths'  => [5],
                'examples' => ['12345'],
                'notes'    => 'Sequential, no check digit.',
            ],
        ],
    ],

    'LT' => [
        'name'       => 'Lithuania',
        'vat_prefix' => 'LT',
        'schemes'    => [
            [
                'code'               => 'pvm',
                'name'               => 'PVM mokėtojo kodas — VAT number',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^(\d{9}|\d{12})$/',
                'lengths'            => [9, 12],
                'check_digit'        => LtPvmValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['213179412'],
                'notes'              => '9 digits for a legal entity, 12 for a natural person; both end in 1 + check.',
            ],
        ],
    ],

    'LU' => [
        'name'       => 'Luxembourg',
        'vat_prefix' => 'LU',
        'schemes'    => [
            [
                'code'               => 'vat',
                'name'               => 'Numéro de TVA',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{8}$/',
                'lengths'            => [8],
                'check_digit'        => LuVatValidator::class,
                'check_digit_offset' => [-2, 2],
                'examples'           => ['20260743'],
            ],
        ],
    ],

    'LV' => [
        'name'       => 'Latvia',
        'vat_prefix' => 'LV',
        'schemes'    => [
            [
                'code'               => 'personas_kods',
                'name'               => 'Personas kods — personal code',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^[0-3]\d{10}$/',
                'lengths'            => [11],
                'mask'               => '######-#####',
                'check_digit'        => LvPersonalCodeValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['32081012341'],
                'notes'              => 'Codes issued since 2017 start with 32 and carry no birth date.',
            ],
            [
                'code'               => 'vat',
                'name'               => 'PVN reģistrācijas numurs — VAT number',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{11}$/',
                'lengths'            => [11],
                'check_digit'        => LvVatValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['40003009497'],
            ],
        ],
    ],

    'MA' => [
        'name'       => 'Morocco',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'if',
                'name'     => 'IF — Identifiant Fiscal',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{7,8}$/',
                'lengths'  => [7, 8],
                'examples' => ['12345678'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'MC' => [
        'name'       => 'Monaco',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'tva',
                'name'               => 'TVA intracommunautaire (French system)',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^[0-9A-Z]{2}\d{9}$/',
                'lengths'            => [11],
                'check_digit'        => FrTvaValidator::class,
                'check_digit_offset' => [0, 2],
                'examples'           => ['96552100554'],
                'notes'              => 'Monaco is inside the French VAT territory; its numbers are quoted with the FR prefix.',
            ],
            [
                'code'               => 'siren',
                'name'               => 'SIREN (French registry)',
                'entity'             => 'legal',
                'vat'                => false,
                'pattern'            => '/^\d{9}$/',
                'lengths'            => [9],
                'check_digit'        => FrSirenValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['552100554'],
            ],
        ],
    ],

    'MD' => [
        'name'       => 'Moldova',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'idno',
                'name'     => 'IDNO — state identification number',
                'entity'   => 'legal',
                'vat'      => true,
                'pattern'  => '/^\d{13}$/',
                'lengths'  => [13],
                'examples' => ['1234567890123'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'ME' => [
        'name'       => 'Montenegro',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'pib',
                'name'     => 'PIB — Poreski identifikacioni broj',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{8}$/',
                'lengths'  => [8],
                'examples' => ['12345678'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'MK' => [
        'name'       => 'North Macedonia',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'edb',
                'name'     => 'ЕДБ — tax number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{13}$/',
                'lengths'  => [13],
                'examples' => ['4030000375897'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'MT' => [
        'name'       => 'Malta',
        'vat_prefix' => 'MT',
        'schemes'    => [
            [
                'code'               => 'vat',
                'name'               => 'VAT registration number',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{8}$/',
                'lengths'            => [8],
                'check_digit'        => MtVatValidator::class,
                'check_digit_offset' => [-2, 2],
                'examples'           => ['12345634'],
            ],
        ],
    ],

    'MX' => [
        'name'       => 'Mexico',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'rfc',
                'name'               => 'RFC — Registro Federal de Contribuyentes',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^[A-Z&]{3,4}\d{6}[A-Z0-9]{3}$/',
                'lengths'            => [12, 13],
                'check_digit'        => MxRfcValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['AAA010101AA1', 'XAXX010101000'],
                'notes'              => '12 characters for a company, 13 for an individual. An RFC may contain "&".',
            ],
        ],
    ],

    'MY' => [
        'name'       => 'Malaysia',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'tin',
                'name'     => 'TIN — Nombor Cukai Pendapatan',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^[A-Z]{1,2}\d{10,11}$/',
                'lengths'  => [11, 12, 13],
                'examples' => ['C1234567890'],
                'notes'    => 'Leading letter(s) encode the taxpayer type. No public check-digit algorithm.',
            ],
        ],
    ],

    'NG' => [
        'name'       => 'Nigeria',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'tin',
                'name'     => 'TIN — Taxpayer Identification Number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{8,10}$/',
                'lengths'  => [8, 9, 10],
                'examples' => ['12345678'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'NI' => [
        'name'       => 'Nicaragua',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'ruc',
                'name'     => 'RUC — Registro Único de Contribuyentes',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^[0-9A-Z]{14}$/',
                'lengths'  => [14],
                'examples' => ['J0310000000004'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'NL' => [
        'name'       => 'Netherlands',
        'vat_prefix' => 'NL',
        'schemes'    => [
            [
                'code'        => 'vat',
                'name'        => 'Btw-identificatienummer',
                'entity'      => 'both',
                'vat'         => true,
                'pattern'     => '/^\d{9}B\d{2}$/',
                'lengths'     => [12],
                'check_digit' => NlBtwValidator::class,
                'examples'    => ['123456782B01'],
                'notes'       => 'Pre-2020 numbers pass the elfproef; post-2020 sole-trader numbers use a mod-97 rule instead.',
            ],
            [
                'code'               => 'bsn',
                'name'               => 'BSN — Burgerservicenummer',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^\d{9}$/',
                'lengths'            => [9],
                'check_digit'        => NlBsnValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['123456782'],
            ],
        ],
    ],

    'NO' => [
        'name'       => 'Norway',
        'vat_prefix' => 'NO',
        'schemes'    => [
            [
                'code'        => 'mva',
                'name'        => 'MVA-nummer — VAT number',
                'entity'      => 'legal',
                'vat'         => true,
                'pattern'     => '/^\d{9}MVA$/',
                'lengths'     => [12],
                'check_digit' => NoOrgnrValidator::class,
                'examples'    => ['974760673MVA'],
                'notes'       => 'The organisation number with the literal suffix MVA.',
            ],
            [
                'code'               => 'orgnr',
                'name'               => 'Organisasjonsnummer',
                'entity'             => 'legal',
                'vat'                => false,
                'pattern'            => '/^\d{9}$/',
                'lengths'            => [9],
                'mask'               => '### ### ###',
                'check_digit'        => NoOrgnrValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['974760673'],
            ],
        ],
    ],

    'NZ' => [
        'name'       => 'New Zealand',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'ird',
                'name'               => 'IRD number',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^\d{8,9}$/',
                'lengths'            => [8, 9],
                'check_digit'        => NzIrdValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['490918507'],
                'notes'              => 'Also the GST registration number.',
            ],
        ],
    ],

    'PA' => [
        'name'       => 'Panama',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'ruc',
                'name'     => 'RUC — Registro Único de Contribuyentes',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^[0-9A-Z]{5,15}$/',
                'lengths'  => [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],
                'examples' => ['155646463225'],
                'notes'    => 'Highly variable format. No public check-digit algorithm.',
            ],
        ],
    ],

    'PE' => [
        'name'       => 'Peru',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'ruc',
                'name'               => 'RUC — Registro Único de Contribuyentes',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^(10|15|16|17|20)\d{9}$/',
                'lengths'            => [11],
                'check_digit'        => PeRucValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['20100070970'],
                'notes'              => 'Leading pair marks the taxpayer type: 10/15/17 individuals, 20 companies.',
            ],
        ],
    ],

    'PH' => [
        'name'       => 'Philippines',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'tin',
                'name'     => 'TIN — Taxpayer Identification Number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{9,12}$/',
                'lengths'  => [9, 10, 11, 12],
                'mask'     => '###-###-###',
                'examples' => ['123456789'],
                'notes'    => 'The optional last digits are a branch code. No public check-digit algorithm.',
            ],
        ],
    ],

    'PK' => [
        'name'       => 'Pakistan',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'ntn',
                'name'     => 'NTN — National Tax Number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{7,8}$/',
                'lengths'  => [7, 8],
                'examples' => ['1234567'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'PL' => [
        'name'       => 'Poland',
        'vat_prefix' => 'PL',
        'schemes'    => [
            [
                'code'               => 'nip',
                'name'               => 'NIP — Numer Identyfikacji Podatkowej',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^\d{10}$/',
                'lengths'            => [10],
                'mask'               => '###-###-##-##',
                'check_digit'        => PlNipValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['1234563218'],
            ],
            [
                'code'               => 'pesel',
                'name'               => 'PESEL — personal number',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^\d{11}$/',
                'lengths'            => [11],
                'check_digit'        => PlPeselValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['44051401359'],
            ],
            [
                'code'               => 'regon',
                'name'               => 'REGON — statistical number',
                'entity'             => 'legal',
                'vat'                => false,
                'pattern'            => '/^(\d{9}|\d{14})$/',
                'lengths'            => [9, 14],
                'check_digit'        => PlRegonValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['123456785', '12345678500010'],
            ],
        ],
    ],

    'PT' => [
        'name'       => 'Portugal',
        'vat_prefix' => 'PT',
        'schemes'    => [
            [
                'code'               => 'nif',
                'name'               => 'NIF/NIPC — Número de Identificação Fiscal',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^[1-9]\d{8}$/',
                'lengths'            => [9],
                'mask'               => '### ### ###',
                'check_digit'        => PtNifValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['123456789'],
                'notes'              => 'The leading digit encodes the taxpayer category; the ranges are extended over time.',
            ],
        ],
    ],

    'PY' => [
        'name'       => 'Paraguay',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'ruc',
                'name'               => 'RUC — Registro Único del Contribuyente',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^\d{5,9}$/',
                'lengths'            => [5, 6, 7, 8, 9],
                'check_digit'        => PyRucValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['800177320'],
            ],
        ],
    ],

    'RO' => [
        'name'       => 'Romania',
        'vat_prefix' => 'RO',
        'schemes'    => [
            [
                'code'               => 'cnp',
                'name'               => 'CNP — Cod Numeric Personal',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^[1-8]\d{12}$/',
                'lengths'            => [13],
                'check_digit'        => RoCnpValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['1800101221144'],
            ],
            [
                'code'               => 'cui',
                'name'               => 'CUI/CIF — Cod Unic de Înregistrare',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{2,10}$/',
                'lengths'            => [2, 3, 4, 5, 6, 7, 8, 9, 10],
                'check_digit'        => RoCuiValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['13548146', '14399840'],
            ],
        ],
    ],

    'RS' => [
        'name'       => 'Serbia',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'pib',
                'name'               => 'PIB — Poreski identifikacioni broj',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^\d{9}$/',
                'lengths'            => [9],
                'check_digit'        => RsPibValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['100000008'],
            ],
        ],
    ],

    'RU' => [
        'name'       => 'Russia',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'        => 'inn',
                'name'        => 'ИНН — Taxpayer Identification Number',
                'entity'      => 'both',
                'vat'         => true,
                'pattern'     => '/^(\d{10}|\d{12})$/',
                'lengths'     => [10, 12],
                'check_digit' => RuInnValidator::class,
                'examples'    => ['7707083893', '770708389324'],
                'notes'       => '10 digits for an organisation, 12 for an individual (two check digits).',
            ],
        ],
    ],

    'SA' => [
        'name'       => 'Saudi Arabia',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'vat',
                'name'     => 'VAT registration number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^3\d{13}3$/',
                'lengths'  => [15],
                'examples' => ['300000000000003'],
                'notes'    => 'Always begins and ends with 3. No public check-digit algorithm.',
            ],
        ],
    ],

    'SE' => [
        'name'       => 'Sweden',
        'vat_prefix' => 'SE',
        'schemes'    => [
            [
                'code'        => 'vat',
                'name'        => 'Momsregistreringsnummer — VAT number',
                'entity'      => 'legal',
                'vat'         => true,
                'pattern'     => '/^\d{10}01$/',
                'lengths'     => [12],
                'check_digit' => SeVatValidator::class,
                'examples'    => ['556036079301'],
            ],
            [
                'code'        => 'personnummer',
                'name'        => 'Personnummer',
                'entity'      => 'natural',
                'vat'         => false,
                'pattern'     => '/^(18|19|20)\d{10}$/',
                'lengths'     => [12],
                'check_digit' => SePersonnummerValidator::class,
                'examples'    => ['198001011231'],
                'notes'       => 'The 10-digit short form is ambiguous with an organisationsnummer, so only the 12-digit form is detected.',
            ],
            [
                'code'               => 'orgnr',
                'name'               => 'Organisationsnummer',
                'entity'             => 'legal',
                'vat'                => false,
                'pattern'            => '/^\d{10}$/',
                'lengths'            => [10],
                'mask'               => '######-####',
                'check_digit'        => SeOrgnrValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['5560360793'],
            ],
        ],
    ],

    'SG' => [
        'name'       => 'Singapore',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'uen',
                'name'     => 'UEN — Unique Entity Number',
                'entity'   => 'legal',
                'vat'      => true,
                'pattern'  => '/^(\d{8}[A-Z]|\d{9}[A-Z]|[TSR]\d{2}[A-Z]{2}\d{4}[A-Z])$/',
                'lengths'  => [9, 10],
                'examples' => ['53012345A', 'T09LL0001B'],
                'notes'    => 'ACRA does not publish the check-letter algorithm.',
            ],
        ],
    ],

    'SI' => [
        'name'       => 'Slovenia',
        'vat_prefix' => 'SI',
        'schemes'    => [
            [
                'code'               => 'vat',
                'name'               => 'Davčna številka — tax number',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^\d{8}$/',
                'lengths'            => [8],
                'check_digit'        => SiVatValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['15012557'],
            ],
        ],
    ],

    'SK' => [
        'name'       => 'Slovakia',
        'vat_prefix' => 'SK',
        'schemes'    => [
            [
                'code'        => 'vat',
                'name'        => 'IČ DPH — VAT number',
                'entity'      => 'legal',
                'vat'         => true,
                'pattern'     => '/^\d{10}$/',
                'lengths'     => [10],
                'check_digit' => SkVatValidator::class,
                'examples'    => ['2020269922'],
                'notes'       => 'No check digit: the whole number must be divisible by 11.',
            ],
            [
                'code'               => 'ico',
                'name'               => 'IČO — organisation number',
                'entity'             => 'legal',
                'vat'                => false,
                'pattern'            => '/^\d{8}$/',
                'lengths'            => [8],
                'check_digit'        => SkIcoValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['31234569'],
                'notes'              => 'Same algorithm as the Czech IČO — a shared Czechoslovak legacy.',
            ],
        ],
    ],

    'SV' => [
        'name'       => 'El Salvador',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'nit',
                'name'     => 'NIT — Número de Identificación Tributaria',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{14}$/',
                'lengths'  => [14],
                'mask'     => '####-######-###-#',
                'examples' => ['06140506901121'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'TH' => [
        'name'       => 'Thailand',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'tin',
                'name'     => 'Taxpayer Identification Number',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{13}$/',
                'lengths'  => [13],
                'examples' => ['1234567890123'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'TN' => [
        'name'       => 'Tunisia',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'mf',
                'name'     => 'MF — Matricule Fiscal',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{7}[A-Z]{3}\d{3}$/',
                'lengths'  => [13],
                'examples' => ['1234567ABC000'],
                'notes'    => 'No public check-digit algorithm.',
            ],
        ],
    ],

    'TR' => [
        'name'       => 'Türkiye',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'tckn',
                'name'               => 'T.C. Kimlik No — citizen number',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^[1-9]\d{10}$/',
                'lengths'            => [11],
                'check_digit'        => TrTcknValidator::class,
                'check_digit_offset' => [-2, 2],
                'examples'           => ['12345678950'],
            ],
            [
                'code'               => 'vkn',
                'name'               => 'VKN — Vergi Kimlik Numarası',
                'entity'             => 'legal',
                'vat'                => true,
                'pattern'            => '/^\d{10}$/',
                'lengths'            => [10],
                'check_digit'        => TrVknValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['4540536920'],
            ],
        ],
    ],

    'TW' => [
        'name'       => 'Taiwan',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'ban',
                'name'     => 'Business Administration Number',
                'entity'   => 'legal',
                'vat'      => true,
                'pattern'  => '/^\d{8}$/',
                'lengths'  => [8],
                'examples' => ['12345675'],
                'notes'    => 'Checksum not implemented; only the shape is verified.',
            ],
        ],
    ],

    'UA' => [
        'name'       => 'Ukraine',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'edrpou',
                'name'     => 'ЄДРПОУ — legal entity register code',
                'entity'   => 'legal',
                'vat'      => true,
                'pattern'  => '/^\d{8}$/',
                'lengths'  => [8],
                'examples' => ['12345678'],
                'notes'    => 'The published two-pass checksum has range-dependent weights; not implemented.',
            ],
            [
                'code'     => 'inn',
                'name'     => 'ІПН — individual taxpayer number',
                'entity'   => 'natural',
                'vat'      => false,
                'pattern'  => '/^\d{10}$/',
                'lengths'  => [10],
                'examples' => ['1234567890'],
                'notes'    => 'Checksum not implemented.',
            ],
        ],
    ],

    'US' => [
        'name'       => 'United States',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'        => 'itin',
                'name'        => 'ITIN — Individual Taxpayer Identification Number',
                'entity'      => 'natural',
                'vat'         => false,
                'pattern'     => '/^9\d{8}$/',
                'lengths'     => [9],
                'mask'        => '###-##-####',
                'check_digit' => UsItinValidator::class,
                'examples'    => ['970512345'],
                'notes'       => 'Always starts with 9; the middle group must be in an IRS-allocated range.',
            ],
            [
                'code'        => 'ein',
                'name'        => 'EIN — Employer Identification Number',
                'entity'      => 'legal',
                'vat'         => false,
                'pattern'     => '/^\d{9}$/',
                'lengths'     => [9],
                'mask'        => '##-#######',
                'check_digit' => UsEinValidator::class,
                'examples'    => ['123456789'],
                'notes'       => 'No check digit. The US levies no VAT; sales tax IDs are issued per state.',
            ],
            [
                'code'        => 'ssn',
                'name'        => 'SSN — Social Security Number',
                'entity'      => 'natural',
                'vat'         => false,
                'pattern'     => '/^\d{9}$/',
                'lengths'     => [9],
                'mask'        => '###-##-####',
                'check_digit' => UsSsnValidator::class,
                'examples'    => ['123456789'],
                'notes'       => 'Shares its shape with the EIN, so an unqualified 9-digit value is read as an EIN; '
                    . 'pass scheme "ssn" explicitly to enforce the SSN ranges.',
            ],
        ],
    ],

    'UY' => [
        'name'       => 'Uruguay',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'rut',
                'name'               => 'RUT — Registro Único Tributario',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^\d{12}$/',
                'lengths'            => [12],
                'mask'               => '##.######.####',
                'check_digit'        => UyRutValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['211003420017'],
            ],
        ],
    ],

    'VE' => [
        'name'       => 'Venezuela',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'               => 'rif',
                'name'               => 'RIF — Registro Único de Información Fiscal',
                'entity'             => 'both',
                'vat'                => true,
                'pattern'            => '/^[VEJPG]\d{9}$/',
                'lengths'            => [10],
                'mask'               => '#-########-#',
                'check_digit'        => VeRifValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['J305329958'],
                'notes'              => 'The leading letter enters the checksum, so it is not merely a type marker.',
            ],
        ],
    ],

    'VN' => [
        'name'       => 'Vietnam',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'mst',
                'name'     => 'MST — Mã số thuế',
                'entity'   => 'both',
                'vat'      => true,
                'pattern'  => '/^\d{10}(\d{3})?$/',
                'lengths'  => [10, 13],
                'examples' => ['0101243150'],
                'notes'    => 'The optional 3-digit suffix identifies a branch. No public check-digit algorithm.',
            ],
        ],
    ],

    'XI' => [
        'name'       => 'United Kingdom (Northern Ireland)',
        'vat_prefix' => 'XI',
        'schemes'    => [
            [
                'code'        => 'vat',
                'name'        => 'VAT registration number (Northern Ireland Protocol)',
                'entity'      => 'legal',
                'vat'         => true,
                'pattern'     => '/^(\d{9}|\d{12})$/',
                'lengths'     => [9, 12],
                'check_digit' => GbVatValidator::class,
                'examples'    => ['123456782'],
                'notes'       => 'Same registrations as GB, quoted with the XI prefix for EU goods trade.',
            ],
        ],
    ],

    'ZA' => [
        'name'       => 'South Africa',
        'vat_prefix' => null,
        'schemes'    => [
            [
                'code'     => 'vat',
                'name'     => 'VAT registration number',
                'entity'   => 'legal',
                'vat'      => true,
                'pattern'  => '/^4\d{9}$/',
                'lengths'  => [10],
                'examples' => ['4012345678'],
                'notes'    => 'Always starts with 4. SARS describes a modulus-10 check whose exact digit ordering is '
                    . 'reported inconsistently, so it is deliberately not enforced here.',
            ],
            [
                'code'     => 'itr',
                'name'     => 'Income tax reference number',
                'entity'   => 'both',
                'vat'      => false,
                'pattern'  => '/^[0-3]\d{9}$/',
                'lengths'  => [10],
                'examples' => ['0123456789'],
                'notes'    => 'Checksum not enforced, same reasoning as the VAT number.',
            ],
        ],
    ],
];
