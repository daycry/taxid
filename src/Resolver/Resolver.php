<?php

declare(strict_types=1);

namespace Daycry\TaxId\Resolver;

use Daycry\TaxId\Contracts\LookupProviderInterface;
use Daycry\TaxId\Contracts\RegistrationCheckInterface;
use Daycry\TaxId\Contracts\ResolverInterface;
use Daycry\TaxId\Core\Parser;
use Daycry\TaxId\DTO\LookupResult;
use Daycry\TaxId\DTO\ParsedTaxId;
use Daycry\TaxId\DTO\ValidationResult;
use Daycry\TaxId\Providers\NullProvider;

/**
 * Combines offline validation with whatever a {@see LookupProviderInterface}
 * can add on top.
 *
 * Two rules it will not bend:
 *
 * - **Validate first, always.** An invalid value never reaches the provider.
 *   Sending a mistyped tax ID to a remote authority is a useless round-trip and
 *   an avoidable disclosure of someone's data.
 * - **Unknown is not "no".** A provider that returns nothing yields
 *   `registered = null`, never `false`. Only a provider that explicitly
 *   implements {@see RegistrationCheckInterface} can produce a `false`.
 *
 * Framework-free: it composes a {@see Parser} and a provider, nothing else.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §6
 */
final class Resolver implements ResolverInterface
{
    public function __construct(
        private readonly Parser $parser = new Parser(),
        private readonly LookupProviderInterface $provider = new NullProvider(),
    ) {
    }

    public function resolve(string|ParsedTaxId $taxId, ?string $country = null): LookupResult
    {
        $parsed = $taxId instanceof ParsedTaxId ? $taxId : $this->parser->tryParse($taxId, $country);

        if ($parsed === null) {
            /** @var string $taxId */
            $validation = $this->parser->validator()->validate($taxId, $country);

            return new LookupResult(null, false, null, null, $validation);
        }

        $entity     = $this->provider->findByTaxId($parsed->countryCode, $parsed->compact);
        $registered = $entity !== null ? true : null;

        if ($registered === null && $this->provider instanceof RegistrationCheckInterface) {
            $registered = $this->provider->isRegistered($parsed->countryCode, $parsed->compact);
        }

        return new LookupResult($parsed, true, $registered, $entity, new ValidationResult(true, []));
    }

    public function provider(): LookupProviderInterface
    {
        return $this->provider;
    }
}
