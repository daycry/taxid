<?php

declare(strict_types=1);

namespace Daycry\TaxId\Providers;

use CodeIgniter\HTTP\CURLRequest;
use Daycry\TaxId\Contracts\LookupProviderInterface;
use Daycry\TaxId\Contracts\RegistrationCheckInterface;
use Daycry\TaxId\DTO\EntityInfo;
use SimpleXMLElement;
use Throwable;

/**
 * The European Commission's VIES service — the one place a tax ID can be
 * checked for *existence* rather than merely for well-formedness.
 *
 * VIES answers one question: is this VAT number currently registered for
 * intra-Community trade in the member state that issued it? When the trader
 * has consented, it also returns their registered name and address; when they
 * have not, it returns `---`, which is mapped to `null` here rather than passed
 * through as data.
 *
 * **Three-state on purpose.** {@see isRegistered()} returns `true`, `false` or
 * `null`, and the difference matters: VIES goes down, rate-limits, and returns
 * `MS_UNAVAILABLE` when a member state's own system is offline. Treating any of
 * those as "not registered" would block legitimate customers, so every failure
 * folds to `null` — unknown — and never throws.
 *
 * **Scope.** Only the EU-27 plus Northern Ireland (`XI`) are in VIES. Anything
 * else returns `null` without a request being made: there is nothing to ask.
 *
 * **Privacy and cost.** Every lookup sends a customer's VAT number to an
 * external service. Results are memoized per instance so one request can't
 * trigger the same call twice, and {@see CachedProvider} should be layered on
 * top for anything longer-lived. VIES also throttles aggressively — do not put
 * it in a loop over a customer table.
 *
 * @see docs/usage.md
 */
final class ViesProvider implements LookupProviderInterface, RegistrationCheckInterface
{
    public const string DEFAULT_ENDPOINT = 'https://ec.europa.eu/taxation_customs/vies/services/checkVatService';

    /** Member states VIES covers, plus Northern Ireland under the Protocol. */
    private const array SUPPORTED = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR',
        'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO',
        'SE', 'SI', 'SK', 'XI',
    ];

    /**
     * Per-instance memo of answers, keyed `CC|TAXID`.
     *
     * Both public methods consult VIES for the same fact, so without this a
     * single `resolve()` would make two identical remote calls.
     *
     * @var array<string, array{registered: bool|null, entity: EntityInfo|null}>
     */
    private array $memo = [];

    public function __construct(
        private readonly CURLRequest $client,
        private readonly string $endpoint = self::DEFAULT_ENDPOINT,
        private readonly int $timeout = 10,
    ) {
    }

    public function findByTaxId(string $countryCode, string $taxId): ?EntityInfo
    {
        return $this->check($countryCode, $taxId)['entity'];
    }

    public function isRegistered(string $countryCode, string $taxId): ?bool
    {
        return $this->check($countryCode, $taxId)['registered'];
    }

    /**
     * @return array{registered: bool|null, entity: EntityInfo|null}
     */
    private function check(string $countryCode, string $taxId): array
    {
        $countryCode = strtoupper($countryCode);
        $key         = $countryCode . '|' . $taxId;

        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        if (! in_array($countryCode, self::SUPPORTED, true)) {
            return $this->memo[$key] = ['registered' => null, 'entity' => null];
        }

        return $this->memo[$key] = $this->request($countryCode, $taxId);
    }

    /**
     * One SOAP round-trip. Every failure mode — DNS, timeout, HTTP error, SOAP
     * fault, malformed XML — collapses to the unknown answer.
     *
     * @return array{registered: bool|null, entity: EntityInfo|null}
     */
    private function request(string $countryCode, string $taxId): array
    {
        $unknown = ['registered' => null, 'entity' => null];

        try {
            $response = $this->client->post($this->endpoint, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction'   => '',
                ],
                'body'        => $this->envelope($countryCode, $taxId),
                'timeout'     => $this->timeout,
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                return $unknown;
            }

            return $this->parse((string) $response->getBody(), $countryCode, $taxId);
        } catch (Throwable) {
            return $unknown;
        }
    }

    /**
     * @return array{registered: bool|null, entity: EntityInfo|null}
     */
    private function parse(string $body, string $countryCode, string $taxId): array
    {
        $unknown = ['registered' => null, 'entity' => null];

        if ($body === '') {
            return $unknown;
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $xml = new SimpleXMLElement($body);
        } catch (Throwable) {
            return $unknown;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $valid = self::firstValue($xml, 'valid');

        if ($valid === null) {
            // A SOAP fault, or any envelope without a checkVat response.
            return $unknown;
        }

        if ($valid !== 'true') {
            return ['registered' => false, 'entity' => null];
        }

        return [
            'registered' => true,
            'entity'     => new EntityInfo(
                countryCode: $countryCode,
                taxId: $taxId,
                name: self::disclosed(self::firstValue($xml, 'name')),
                address: self::disclosed(self::firstValue($xml, 'address')),
                resolvedBy: 'vies',
            ),
        ];
    }

    /**
     * Pull an element's text by local name, whatever namespace prefix the
     * response happens to use — VIES has changed prefixes before, and XPath's
     * `local-name()` sidesteps the whole question.
     */
    private static function firstValue(SimpleXMLElement $xml, string $localName): ?string
    {
        $nodes = $xml->xpath('//*[local-name()="' . $localName . '"]');

        if ($nodes === false || $nodes === null || $nodes === []) {
            return null;
        }

        return trim((string) $nodes[0]);
    }

    /**
     * VIES writes `---` when the trader has not consented to disclosure. That
     * is an absence of data, not a name, so it becomes `null`.
     */
    private static function disclosed(?string $value): ?string
    {
        if ($value === null || $value === '' || trim($value, '- ') === '') {
            return null;
        }

        return $value;
    }

    private function envelope(string $countryCode, string $taxId): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"'
            . ' xmlns:urn="urn:ec.europa.eu:taxud:vies:services:checkVat:types">'
            . '<soapenv:Header/><soapenv:Body><urn:checkVat>'
            . '<urn:countryCode>' . htmlspecialchars($countryCode, ENT_XML1) . '</urn:countryCode>'
            . '<urn:vatNumber>' . htmlspecialchars($taxId, ENT_XML1) . '</urn:vatNumber>'
            . '</urn:checkVat></soapenv:Body></soapenv:Envelope>';
    }
}
