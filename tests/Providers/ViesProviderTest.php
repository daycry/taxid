<?php

declare(strict_types=1);

namespace Tests\Providers;

use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Daycry\TaxId\Providers\ViesProvider;
use Tests\Support\RecordingCurlRequest;

/**
 * VIES, entirely offline.
 *
 * Every response shape is canned, because a test suite that reaches the
 * European Commission is a test suite that fails on a train. What matters here
 * is that each shape maps to the right one of the three answers — and, above
 * all, that failures never masquerade as "not registered".
 */
final class ViesProviderTest extends CIUnitTestCase
{
    private const string VALID_RESPONSE = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Body>
            <checkVatResponse xmlns="urn:ec.europa.eu:taxud:vies:services:checkVat:types">
              <countryCode>ES</countryCode>
              <vatNumber>B12345674</vatNumber>
              <requestDate>2026-07-27+02:00</requestDate>
              <valid>true</valid>
              <name>ACME SL</name>
              <address>CALLE MAYOR 1, MADRID</address>
            </checkVatResponse>
          </soap:Body>
        </soap:Envelope>
        XML;

    private const string INVALID_RESPONSE = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Body>
            <checkVatResponse xmlns="urn:ec.europa.eu:taxud:vies:services:checkVat:types">
              <countryCode>ES</countryCode>
              <vatNumber>B99999999</vatNumber>
              <valid>false</valid>
              <name>---</name>
              <address>---</address>
            </checkVatResponse>
          </soap:Body>
        </soap:Envelope>
        XML;

    private const string UNDISCLOSED_RESPONSE = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Body>
            <checkVatResponse xmlns="urn:ec.europa.eu:taxud:vies:services:checkVat:types">
              <valid>true</valid>
              <name>---</name>
              <address>---</address>
            </checkVatResponse>
          </soap:Body>
        </soap:Envelope>
        XML;

    private const string FAULT_RESPONSE = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Body>
            <soap:Fault>
              <faultcode>soap:Server</faultcode>
              <faultstring>MS_UNAVAILABLE</faultstring>
            </soap:Fault>
          </soap:Body>
        </soap:Envelope>
        XML;

    public function testARegisteredNumberYieldsNameAndAddress(): void
    {
        $provider = $this->provider(self::VALID_RESPONSE);

        $entity = $provider->findByTaxId('ES', 'B12345674');

        self::assertNotNull($entity);
        self::assertSame('ACME SL', $entity->name);
        self::assertSame('CALLE MAYOR 1, MADRID', $entity->address);
        self::assertSame('vies', $entity->resolvedBy);
        self::assertTrue($provider->isRegistered('ES', 'B12345674'));
    }

    public function testAnUnregisteredNumberIsAuthoritativelyFalse(): void
    {
        $provider = $this->provider(self::INVALID_RESPONSE);

        self::assertNull($provider->findByTaxId('ES', 'B99999999'));
        self::assertFalse($provider->isRegistered('ES', 'B99999999'), 'VIES answered; false is a real answer.');
    }

    /**
     * VIES writes `---` when the trader has not consented to disclosure. That is
     * an absence of data, not a company called "---".
     */
    public function testUndisclosedNamesBecomeNull(): void
    {
        $entity = $this->provider(self::UNDISCLOSED_RESPONSE)->findByTaxId('ES', 'B12345674');

        self::assertNotNull($entity, 'The number is still registered.');
        self::assertNull($entity->name);
        self::assertNull($entity->address);
    }

    public function testASoapFaultIsUnknownNotUnregistered(): void
    {
        $provider = $this->provider(self::FAULT_RESPONSE);

        self::assertNull($provider->findByTaxId('ES', 'B12345674'));
        self::assertNull(
            $provider->isRegistered('ES', 'B12345674'),
            'A member state being offline must never block a legitimate customer.',
        );
    }

    public function testAnHttpErrorIsUnknown(): void
    {
        $provider = $this->provider('', 503);

        self::assertNull($provider->isRegistered('ES', 'B12345674'));
    }

    public function testMalformedXmlIsUnknown(): void
    {
        $provider = $this->provider('<not-xml');

        self::assertNull($provider->isRegistered('ES', 'B12345674'));
    }

    public function testANetworkFailureNeverEscapes(): void
    {
        $provider = new ViesProvider(new RecordingCurlRequest(null));

        self::assertNull($provider->findByTaxId('ES', 'B12345674'));
        self::assertNull($provider->isRegistered('ES', 'B12345674'));
    }

    public function testNonEuCountriesAreNotEvenAsked(): void
    {
        $client   = new RecordingCurlRequest($this->response(self::VALID_RESPONSE));
        $provider = new ViesProvider($client);

        self::assertNull($provider->findByTaxId('BR', '11144477735'));
        self::assertNull($provider->isRegistered('BR', '11144477735'));
        self::assertSame(0, $client->calls, 'VIES does not cover Brazil; there is nothing to ask.');
    }

    public function testNorthernIrelandIsCovered(): void
    {
        $client   = new RecordingCurlRequest($this->response(self::VALID_RESPONSE));
        $provider = new ViesProvider($client);

        $provider->isRegistered('XI', '123456782');

        self::assertSame(1, $client->calls);
    }

    /**
     * Both public methods answer the same underlying question, so one lookup
     * must serve both — otherwise every `resolve()` doubles the load on a
     * rate-limited public service.
     */
    public function testRepeatedQuestionsAboutTheSameNumberHitTheNetworkOnce(): void
    {
        $client   = new RecordingCurlRequest($this->response(self::VALID_RESPONSE));
        $provider = new ViesProvider($client);

        $provider->findByTaxId('ES', 'B12345674');
        $provider->isRegistered('ES', 'B12345674');
        $provider->findByTaxId('ES', 'B12345674');

        self::assertSame(1, $client->calls);
    }

    public function testTheRequestCarriesTheExpectedEndpointAndSoapBody(): void
    {
        $client   = new RecordingCurlRequest($this->response(self::VALID_RESPONSE));
        $provider = new ViesProvider($client, 'https://example.test/checkVatService', 3);

        $provider->findByTaxId('ES', 'B12345674');

        self::assertSame('https://example.test/checkVatService', $client->lastUrl);
        self::assertSame(3, $client->lastOptions['timeout'] ?? null);

        $body = (string) ($client->lastOptions['body'] ?? '');

        self::assertStringContainsString('<urn:countryCode>ES</urn:countryCode>', $body);
        self::assertStringContainsString('<urn:vatNumber>B12345674</urn:vatNumber>', $body);
        self::assertStringContainsString('urn:ec.europa.eu:taxud:vies:services:checkVat:types', $body);
    }

    private function provider(string $body, int $status = 200): ViesProvider
    {
        return new ViesProvider(new RecordingCurlRequest($this->response($body, $status)));
    }

    private function response(string $body, int $status = 200): ResponseInterface
    {
        $response = new Response(new App());
        $response->setStatusCode($status);
        $response->setBody($body);

        return $response;
    }
}
