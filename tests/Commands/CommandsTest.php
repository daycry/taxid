<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\StreamFilterTrait;
use Daycry\TaxId\Commands\PublishCommand;
use Daycry\TaxId\Config\TaxId as TaxIdConfig;

/**
 * The five `taxid:*` spark commands, run through CI4's real discovery and
 * execution path — `service('commands')->run()`, the same call
 * `CodeIgniter\CLI\Console::run()` makes for an actual `php spark …`.
 *
 * Two details make this work, and both are easy to get wrong:
 *
 * **Output capture.** `CLI::write()`/`error()`/`table()` all go through
 * `CLI::fwrite(STDOUT, …)`, which bypasses PHP's `ob_*` buffering — so the
 * usual `ob_start()` trick sees nothing. CI4's `StreamFilterTrait` attaches a
 * filter to STDOUT/STDERR instead, which is what its own test doubles use.
 *
 * **Option capture.** `CLI::getOption()` reads a process-wide static array
 * populated from `$_SERVER['argv']` by `CLI::init()`, NOT from the `$params`
 * passed to `run()`. {@see runSpark()} reproduces exactly what a real
 * invocation does: seed `argv`, re-init, then derive `$params` the same way.
 */
final class CommandsTest extends CIUnitTestCase
{
    use StreamFilterTrait;

    protected function tearDown(): void
    {
        parent::tearDown();

        config(TaxIdConfig::class)->defaultCountry = null;
        $this->resetServices();
    }

    // -------------------------------------------------------- taxid:validate

    public function testValidateExitsZeroForAValidTaxId(): void
    {
        $exit = $this->runSpark(['taxid:validate', '12345678Z', '--country', 'ES']);

        self::assertSame(EXIT_SUCCESS, $exit);
        self::assertStringContainsString('VALID', $this->getStreamFilterBuffer());
    }

    public function testValidateExitsOneForAnInvalidTaxId(): void
    {
        $exit = $this->runSpark(['taxid:validate', '12345678A', '--country', 'ES']);

        self::assertSame(EXIT_ERROR, $exit);
        self::assertStringContainsString('check_digit_failed', $this->getStreamFilterBuffer());
    }

    public function testValidateInfersTheCountryFromAVatPrefix(): void
    {
        self::assertSame(EXIT_SUCCESS, $this->runSpark(['taxid:validate', 'ESB12345674']));
    }

    public function testValidateJsonOutput(): void
    {
        $this->runSpark(['taxid:validate', '12345678Z', '--country', 'ES', '--json']);

        $payload = $this->decodeJson();

        self::assertTrue($payload['valid']);
        self::assertSame('ES', $payload['country']);
        self::assertSame('nif', $payload['scheme']);
        self::assertNull($payload['violation']);
    }

    public function testValidateJsonReportsTheViolation(): void
    {
        $this->runSpark(['taxid:validate', '12345678A', '--country', 'ES', '--json']);

        $payload = $this->decodeJson();

        self::assertFalse($payload['valid']);
        self::assertSame('check_digit_failed', $payload['violation']['code']);
    }

    public function testValidateCanDemandAScheme(): void
    {
        self::assertSame(EXIT_ERROR, $this->runSpark(['taxid:validate', '12345678Z', '--country', 'ES', '--scheme', 'cif']));
        self::assertSame(EXIT_SUCCESS, $this->runSpark(['taxid:validate', 'B12345674', '--country', 'ES', '--scheme', 'cif']));
    }

    public function testValidateFallsBackToTheConfiguredCountry(): void
    {
        config(TaxIdConfig::class)->defaultCountry = 'ES';
        $this->resetServices();

        self::assertSame(EXIT_SUCCESS, $this->runSpark(['taxid:validate', '12345678Z']));
    }

    // ----------------------------------------------------------- taxid:parse

    public function testParsePrintsATable(): void
    {
        $exit   = $this->runSpark(['taxid:parse', '111.444.777-35', '--country', 'BR']);
        $output = $this->getStreamFilterBuffer();

        self::assertSame(EXIT_SUCCESS, $exit);
        self::assertStringContainsString('cpf', $output);
        self::assertStringContainsString('111.444.777-35', $output);
    }

    public function testParseJsonCarriesEveryDerivedForm(): void
    {
        $this->runSpark(['taxid:parse', 'B12345674', '--country', 'ES', '--json']);

        $payload = $this->decodeJson();

        self::assertTrue($payload['parsed']);
        self::assertSame('cif', $payload['scheme']);
        self::assertSame('legal', $payload['entity_type']);
        self::assertSame('ESB12345674', $payload['vat_number']);
        self::assertSame('B-1234567-4', $payload['print']);
        self::assertSame('*******74', $payload['anonymized']);
    }

    public function testParseFailsOnAnInvalidValue(): void
    {
        $exit = $this->runSpark(['taxid:parse', '12345678A', '--country', 'ES']);

        self::assertSame(EXIT_ERROR, $exit);
        self::assertStringContainsString('CANNOT PARSE', $this->getStreamFilterBuffer());
    }

    // ---------------------------------------------------------- taxid:lookup

    /**
     * With no provider the answer is "unknown", and the command must exit 0 —
     * a VIES outage is not a validation failure.
     */
    public function testLookupWithoutAProviderExitsZeroAndReportsUnknown(): void
    {
        $exit = $this->runSpark(['taxid:lookup', 'ESB12345674']);

        self::assertSame(EXIT_SUCCESS, $exit);
        self::assertStringContainsString('REGISTRATION UNKNOWN', $this->getStreamFilterBuffer());
    }

    public function testLookupExitsOneForAnInvalidTaxId(): void
    {
        self::assertSame(EXIT_ERROR, $this->runSpark(['taxid:lookup', '12345678A', '--country', 'ES']));
    }

    public function testLookupJsonPreservesTheThreeStateAnswer(): void
    {
        $this->runSpark(['taxid:lookup', 'ESB12345674', '--json']);

        $payload = $this->decodeJson();

        self::assertTrue($payload['valid']);
        self::assertNull($payload['registered'], 'Unknown must serialize as null, never as false.');
        self::assertSame('ESB12345674', $payload['vat_number']);
    }

    // ------------------------------------------------------- taxid:countries

    public function testCountriesListsTheWholeRegistry(): void
    {
        $exit   = $this->runSpark(['taxid:countries']);
        $output = $this->getStreamFilterBuffer();

        self::assertSame(EXIT_SUCCESS, $exit);
        self::assertStringContainsString('Spain', $output);
        self::assertStringContainsString('countries', $output);
    }

    public function testCountriesDetailsOneCountry(): void
    {
        $this->runSpark(['taxid:countries', 'ES', '--json']);

        $payload = $this->decodeJson();

        self::assertSame('ES', $payload['country']);
        self::assertSame('ES', $payload['vat_prefix']);
        self::assertSame('nif', $payload['schemes'][0]['code']);
        self::assertTrue($payload['schemes'][0]['check_digit']);
    }

    public function testCountriesRejectsAnUnknownCode(): void
    {
        self::assertSame(EXIT_ERROR, $this->runSpark(['taxid:countries', 'ZZ']));
    }

    // --------------------------------------------------------- taxid:publish

    public function testPublishWritesAConfigThatExtendsThePackageOne(): void
    {
        $target = $this->tempConfigPath();

        $command             = new PublishCommand(service('logger'), service('commands'));
        $command->targetPath = $target;

        self::assertSame(EXIT_SUCCESS, $command->run([]));

        $written = (string) file_get_contents($target);

        self::assertStringContainsString('namespace Config;', $written);
        self::assertStringContainsString('use Daycry\TaxId\Config\TaxId as BaseTaxId;', $written);
        self::assertStringContainsString('class TaxId extends BaseTaxId', $written);
        self::assertStringNotContainsString('extends BaseConfig', $written);

        unlink($target);
    }

    public function testPublishRefusesToOverwriteWithoutForce(): void
    {
        $target = $this->tempConfigPath();
        file_put_contents($target, '<?php // existing');

        $command             = new PublishCommand(service('logger'), service('commands'));
        $command->targetPath = $target;

        self::assertSame(EXIT_ERROR, $command->run([]));
        self::assertStringContainsString('already exists', $this->getStreamFilterBuffer());
        self::assertSame('<?php // existing', file_get_contents($target));

        unlink($target);
    }

    public function testPublishOverwritesWithForce(): void
    {
        $target = $this->tempConfigPath();
        file_put_contents($target, '<?php // existing');

        $command             = new PublishCommand(service('logger'), service('commands'));
        $command->targetPath = $target;

        self::assertSame(EXIT_SUCCESS, $command->run(['force' => null]));
        self::assertStringContainsString('class TaxId extends BaseTaxId', (string) file_get_contents($target));

        unlink($target);
    }

    // ------------------------------------------------------------- machinery

    /**
     * Reproduce a real `php spark …` invocation: seed `$_SERVER['argv']`,
     * re-init the CLI so `getOption()` sees the flags, and derive `$params`
     * exactly as `Console::run()` does.
     *
     * @param string[] $argv
     */
    private function runSpark(array $argv): int
    {
        $command = array_merge(['spark'], $argv);

        service('superglobals')->setServer('argv', $command);
        service('superglobals')->setServer('argc', count($command));

        CLI::init();

        $params = array_merge(CLI::getSegments(), CLI::getOptions());
        $name   = array_shift($params);

        /** @var int $result */
        $result = service('commands')->run((string) $name, $params);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(): array
    {
        $buffer = trim($this->getStreamFilterBuffer());
        $start  = strpos($buffer, '{');
        $start  = $start === false ? strpos($buffer, '[') : $start;

        self::assertIsInt($start, 'No JSON found in the command output: ' . $buffer);

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode(substr($buffer, $start), true);

        self::assertIsArray($decoded, 'Command output is not valid JSON: ' . $buffer);

        return $decoded;
    }

    private function tempConfigPath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'taxid_publish_test_' . getmypid() . '.php';
    }
}
