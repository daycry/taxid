<?php

declare(strict_types=1);

namespace Daycry\TaxId\Providers;

use Daycry\TaxId\Contracts\LookupProviderInterface;
use Daycry\TaxId\DTO\EntityInfo;
use Daycry\TaxId\Enums\EntityType;
use Daycry\TaxId\Models\TaxEntityModel;
use Throwable;

/**
 * Taxpayer data from the local `tax_entities` table.
 *
 * Deliberately does **not** implement
 * {@see \Daycry\TaxId\Contracts\RegistrationCheckInterface}: a missing row means
 * "we have no record", which is not the same as "this taxpayer does not exist".
 * Claiming otherwise would turn an incomplete local table into false negatives
 * for real customers, so an unknown ID stays `registered = null`.
 *
 * A database error is swallowed to `null` for the same reason every provider
 * swallows its failures — resolution is an enrichment step, and it must not be
 * able to break validation.
 */
final class DatabaseProvider implements LookupProviderInterface
{
    public function __construct(
        private readonly TaxEntityModel $model,
    ) {
    }

    public function findByTaxId(string $countryCode, string $taxId): ?EntityInfo
    {
        try {
            $row = $this->model->findByTaxId($countryCode, $taxId);
        } catch (Throwable) {
            return null;
        }

        if ($row === null) {
            return null;
        }

        return new EntityInfo(
            countryCode: strtoupper((string) ($row['country_code'] ?? $countryCode)),
            taxId: (string) ($row['tax_id'] ?? $taxId),
            name: self::nullableString($row['name'] ?? null),
            address: self::nullableString($row['address'] ?? null),
            entityType: self::entityType($row['entity_type'] ?? null),
            resolvedBy: self::nullableString($row['source'] ?? null) ?? 'database',
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * An unrecognised `entity_type` is treated as absent rather than as an
     * error: the column is free-form in an app-owned table.
     */
    private static function entityType(mixed $value): ?EntityType
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return EntityType::tryFrom($value);
    }
}
