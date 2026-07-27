<?php

declare(strict_types=1);

namespace Daycry\TaxId;

use Daycry\TaxId\Contracts\LookupProviderInterface;
use Daycry\TaxId\Contracts\ParserInterface;
use Daycry\TaxId\Contracts\ResolverInterface;
use Daycry\TaxId\Contracts\ValidatorInterface;
use Daycry\TaxId\Core\Parser;
use Daycry\TaxId\Core\Validator;
use Daycry\TaxId\DTO\LookupResult;
use Daycry\TaxId\DTO\ParsedTaxId;
use Daycry\TaxId\DTO\ValidationResult;
use Daycry\TaxId\Enums\TaxIdFormat;
use Daycry\TaxId\Providers\NullProvider;
use Daycry\TaxId\Registry\CountryProfile;
use Daycry\TaxId\Registry\Registry;
use Daycry\TaxId\Registry\TaxIdScheme;
use Daycry\TaxId\Resolver\Resolver;

/**
 * Public API facade for tax identification numbers: validation, parsing,
 * formatting and — opt-in — taxpayer resolution.
 *
 * Composes Validator → Parser → Resolver and implements all three contracts by
 * delegation. Default-constructible with automatic wiring, so the zero-config
 * path needs no container and no framework:
 *
 * ```php
 * $taxid = new \Daycry\TaxId\TaxId();
 *
 * $taxid->isValid('12345678Z', 'ES');   // true
 * $taxid->isValid('ESB12345678');       // true — country inferred from the VAT prefix
 * $taxid->parse('123.456.789-09', 'BR')->scheme;   // 'cpf'
 * ```
 *
 * **The country is optional, not magic.** When omitted it is inferred from a
 * leading VAT/country prefix and nothing else — never from the digits. A bare
 * `123456789` is a valid tax ID in dozens of jurisdictions, so guessing one
 * would produce confident nonsense; the caller gets `CountryRequired` instead.
 *
 * **What "valid" means here.** Structure plus check digit: *could this have been
 * issued?* It never means *was it issued?* — for that, configure a provider and
 * use {@see resolve()}, which for EU VAT numbers can ask VIES.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §7
 */
final class TaxId implements ValidatorInterface, ParserInterface, ResolverInterface
{
    private readonly Validator $validator;
    private readonly Parser $parser;
    private readonly Resolver $resolver;

    public function __construct(
        private readonly Registry $registry = new Registry(),
        LookupProviderInterface $provider = new NullProvider(),
    ) {
        $this->validator = new Validator($registry);
        $this->parser    = new Parser($this->validator);
        $this->resolver  = new Resolver($this->parser, $provider);
    }

    // ---------------------------------------------------------------- validation

    public function validate(string|ParsedTaxId $taxId, ?string $country = null, ?string $scheme = null): ValidationResult
    {
        return $this->validator->validate($taxId, $country, $scheme);
    }

    public function isValid(string|ParsedTaxId $taxId, ?string $country = null, ?string $scheme = null): bool
    {
        return $this->validator->isValid($taxId, $country, $scheme);
    }

    // ------------------------------------------------------- parsing / formatting

    public function normalize(string $taxId, ?string $country = null): string
    {
        return $this->parser->normalize($taxId, $country);
    }

    public function parse(string $taxId, ?string $country = null): ParsedTaxId
    {
        return $this->parser->parse($taxId, $country);
    }

    public function tryParse(string $taxId, ?string $country = null): ?ParsedTaxId
    {
        return $this->parser->tryParse($taxId, $country);
    }

    public function format(string|ParsedTaxId $taxId, TaxIdFormat $f = TaxIdFormat::Print, ?string $country = null): string
    {
        return $this->parser->format($taxId, $f, $country);
    }

    // ---------------------------------------------------------------- resolution

    /**
     * Validate, then ask the configured provider what it knows about the
     * taxpayer. With the default {@see NullProvider} this is validation plus an
     * explicit "nobody looked" (`registered === null`).
     */
    public function resolve(string|ParsedTaxId $taxId, ?string $country = null): LookupResult
    {
        return $this->resolver->resolve($taxId, $country);
    }

    // ------------------------------------------------------- registry introspection

    public function supports(string $country): bool
    {
        return $this->registry->has($country);
    }

    /**
     * Every country the registry covers, sorted.
     *
     * @return string[]
     */
    public function countries(): array
    {
        return $this->registry->countries();
    }

    public function country(string $country): CountryProfile
    {
        return $this->registry->get($country);
    }

    /**
     * A country's tax-ID schemes, in registry order (most specific first).
     *
     * @return TaxIdScheme[]
     */
    public function schemes(string $country): array
    {
        return array_values($this->registry->get($country)->schemes);
    }

    public function scheme(string $country, string $scheme): TaxIdScheme
    {
        return $this->registry->scheme($country, $scheme);
    }

    // ------------------------------------------------------------ sub-services

    public function validator(): Validator
    {
        return $this->validator;
    }

    public function parser(): Parser
    {
        return $this->parser;
    }

    public function resolver(): Resolver
    {
        return $this->resolver;
    }

    public function registry(): Registry
    {
        return $this->registry;
    }
}
