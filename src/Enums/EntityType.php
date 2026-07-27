<?php

declare(strict_types=1);

namespace Daycry\TaxId\Enums;

/**
 * Who a tax-ID scheme identifies.
 *
 * Backed by a stable string because it is persisted (the `tax_entities`
 * table) and serialized (spark `--json` output).
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §4.1
 */
enum EntityType: string
{
    /** A natural person (e.g. Spain's NIF, Brazil's CPF, a US SSN). */
    case Natural = 'natural';

    /** A legal entity — company, association, public body (e.g. Spain's CIF, Brazil's CNPJ). */
    case Legal = 'legal';

    /**
     * Either: the same scheme is issued to persons and companies alike
     * (e.g. Portugal's NIF, most EU VAT numbers, Chile's RUT).
     */
    case Both = 'both';

    /**
     * Whether a value of this type can belong to `$other`.
     *
     * {@see self::Both} is compatible with everything, in both directions;
     * `Natural` and `Legal` are only compatible with themselves.
     */
    public function accepts(self $other): bool
    {
        return $this === self::Both || $other === self::Both || $this === $other;
    }
}
