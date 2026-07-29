<?php

declare(strict_types=1);

namespace Tests\National;

use Daycry\TaxId\TaxId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * European check digits, tested on the cases the bundled examples don't reach.
 *
 * The registry's data-integrity test already proves every example validates and
 * that each checksum rejects single-digit mutations. What it cannot cover is the
 * *interesting* behaviour: the branches, the exceptions, and the values that
 * look plausible but must be refused. Those live here.
 */
final class EuropeValidatorsTest extends TestCase
{
    private TaxId $taxid;

    protected function setUp(): void
    {
        $this->taxid = new TaxId();
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string[], 3: string[]}>
     */
    public static function schemeProvider(): iterable
    {
        yield 'ES NIF' => ['ES', 'nif', ['12345678Z', '00000000T'], ['12345678A', '1234567Z']];

        yield 'ES NIE' => [
            'ES',
            'nie',
            ['X1234567L', 'Y1234567X', 'Z1234567R'],
            ['X1234567X', 'Y1234567L', 'W1234567L'],
        ];

        // The organisation letter decides whether the control is a digit or a
        // letter, so the same body has two possible valid endings.
        yield 'ES CIF' => [
            'ES',
            'cif',
            ['B12345674', 'A58818501', 'P1234567D'],
            ['B1234567J', 'A58818509', 'X12345674'],
        ];

        yield 'PT NIF' => ['PT', 'nif', ['123456789', '501442600'], ['123456788', '023456789']];

        yield 'FR SIREN' => ['FR', 'siren', ['552100554'], ['552100555']];
        yield 'FR SIRET' => ['FR', 'siret', ['55210055400013'], ['55210055400014']];
        yield 'FR TVA'   => ['FR', 'tva', ['96552100554'], ['95552100554', '96552100555']];
        yield 'FR SPI' => ['FR', 'spi', ['1234567890066', '0123456789211'], ['1234567890067', '4234567890066', '123456789006']];

        yield 'DE VAT'  => ['DE', 'vat', ['136695976'], ['136695975']];
        yield 'DE IdNr' => ['DE', 'idnr', ['12345678903'], ['12345678904']];

        yield 'IT partita IVA' => ['IT', 'piva', ['00743110157'], ['00743110158']];
        yield 'IT codice fiscale' => [
            'IT',
            'cf',
            ['RSSMRA85T10A562S'],
            ['RSSMRA85T10A562T', 'RSSMRA85Z10A562S'],
        ];

        yield 'NL BTW' => ['NL', 'vat', ['123456782B01'], ['123456783B01', '123456782B00']];
        yield 'NL BSN' => ['NL', 'bsn', ['123456782'], ['123456783', '000000000']];

        yield 'BE VAT' => ['BE', 'vat', ['0776091951'], ['0776091952']];
        yield 'BE NN'  => ['BE', 'nn', ['85073003328'], ['85073003329']];

        yield 'LU VAT' => ['LU', 'vat', ['20260743'], ['20260744']];
        yield 'AT UID' => ['AT', 'uid', ['U10223006'], ['U10223007']];
        yield 'AT Steuernummer' => ['AT', 'steuernummer', ['123456782', '591199013'], ['123456783', '12345678', '1234567820']];
        yield 'DK CVR' => ['DK', 'cvr', ['13585628'], ['13585629']];

        yield 'SE orgnr'        => ['SE', 'orgnr', ['5560360793'], ['5560360794']];
        yield 'SE VAT'          => ['SE', 'vat', ['556036079301'], ['556036079401']];
        yield 'SE personnummer' => ['SE', 'personnummer', ['198001011231'], ['198001011232']];

        yield 'FI Y-tunnus' => ['FI', 'ytunnus', ['02012566'], ['02012567']];

        yield 'FI henkilötunnus' => [
            'FI',
            'henkilotunnus',
            ['010190123M', '010106A123Y', '290280123X'],
            ['010190123N', '010190123G', '011390123M', '01019012345', '000190123M'],
        ];

        yield 'PL NIP'   => ['PL', 'nip', ['1234563218'], ['1234563219']];
        yield 'PL PESEL' => ['PL', 'pesel', ['44051401359'], ['44051401358']];
        yield 'PL REGON' => ['PL', 'regon', ['123456785', '12345678500010'], ['123456786']];

        yield 'CZ IČO' => ['CZ', 'ico', ['00006947'], ['00006948']];
        yield 'CZ DIČ' => ['CZ', 'dic', ['00006947', '7103191238'], ['7103191239']];

        yield 'SK VAT' => ['SK', 'vat', ['2020269922'], ['2020269923', '2120269922']];
        yield 'SK IČO' => ['SK', 'ico', ['31234569'], ['31234568']];

        yield 'HU ANUM'    => ['HU', 'anum', ['10778757'], ['10778758']];
        yield 'HU adószám' => ['HU', 'adoszam', ['10778757443'], ['10778758443']];
        yield 'HU adóazonosító' => ['HU', 'adoazonosito', ['8123456786', '8401234565'], ['8123456787', '8000000130', '7123456786', '812345678']];

        yield 'SI VAT' => ['SI', 'vat', ['15012557'], ['15012558']];
        yield 'HR OIB' => ['HR', 'oib', ['69435151530'], ['69435151531']];

        yield 'RO CUI' => ['RO', 'cui', ['13548146', '14399840'], ['13548147']];
        yield 'RO CNP' => ['RO', 'cnp', ['1800101221144'], ['1800101221145']];

        yield 'BG EIK' => ['BG', 'eik', ['831902483'], ['831902484']];
        yield 'BG EGN' => ['BG', 'egn', ['7523169263'], ['7523169264', '7599169263']];

        yield 'GR AFM' => ['GR', 'afm', ['094259216'], ['094259217']];

        yield 'IE VAT' => ['IE', 'vat', ['1234567T', '1234567FA'], ['1234567A', '1234567FB']];

        yield 'GB VAT'  => ['GB', 'vat', ['123456782', 'GD001', 'HA599'], ['123456783', 'GD501']];
        yield 'GB NINO' => ['GB', 'nino', ['AB123456C'], ['DB123456C', 'AB123456E']];

        yield 'EE VAT'       => ['EE', 'vat', ['100594102'], ['100594103']];
        yield 'EE isikukood' => ['EE', 'isikukood', ['37605030299'], ['37605030298']];

        yield 'LV VAT'           => ['LV', 'vat', ['40003009497'], ['40003009498']];
        yield 'LV personas kods' => ['LV', 'personas_kods', ['32081012341'], ['32081012342']];

        yield 'LT PVM' => ['LT', 'pvm', ['213179412'], ['213179413', '213179422']];
        yield 'LT asmens kodas' => [
            'LT',
            'asmens_kodas',
            ['39001011237', '49001011238', '90000001235'],
            ['39001011238', '79001011237', '39013011237', '3900101123'],
        ];
        yield 'MT VAT' => ['MT', 'vat', ['12345634'], ['12345635']];
        yield 'CY VAT' => ['CY', 'vat', ['10259033P'], ['10259033Q']];

        yield 'NO orgnr' => ['NO', 'orgnr', ['974760673'], ['974760674']];
        yield 'NO MVA'   => ['NO', 'mva', ['974760673MVA'], ['974760674MVA']];

        yield 'IS kennitala' => ['IS', 'kennitala', ['1203894599'], ['1203894589', '9903894599']];

        yield 'CH UID'  => ['CH', 'uid', ['CHE116281839'], ['CHE116281838']];
        yield 'CH MWST' => ['CH', 'mwst', ['CHE116281839MWST'], ['CHE116281838MWST']];

        yield 'RS PIB' => ['RS', 'pib', ['100000008'], ['100000009']];
        yield 'RU INN' => ['RU', 'inn', ['7707083893', '770708389324'], ['7707083894', '770708389325']];

        yield 'TR VKN'  => ['TR', 'vkn', ['4540536920'], ['4540536921']];
        yield 'TR TCKN' => ['TR', 'tckn', ['12345678950'], ['12345678951', '02345678950']];
    }

    /**
     * @param string[] $valid
     * @param string[] $invalid
     */
    #[DataProvider('schemeProvider')]
    public function testScheme(string $country, string $scheme, array $valid, array $invalid): void
    {
        foreach ($valid as $value) {
            self::assertTrue(
                $this->taxid->isValid($value, $country, $scheme),
                sprintf('%s/%s should accept "%s": %s', $country, $scheme, $value, (string) $this->taxid->validate($value, $country, $scheme)->error()),
            );
        }

        foreach ($invalid as $value) {
            self::assertFalse(
                $this->taxid->isValid($value, $country, $scheme),
                sprintf('%s/%s should reject "%s"', $country, $scheme, $value),
            );
        }
    }

    // ------------------------------------------------ country-specific branches

    /**
     * Spain writes the CIF control as a letter for public bodies and
     * congregations, as a digit for limited companies, and accepts either for
     * everything else. Getting that wrong rejects real organisations.
     */
    public function testSpanishCifControlCharacterDependsOnTheOrganisationLetter(): void
    {
        // P (public body) must use the letter form.
        self::assertTrue($this->taxid->isValid('P1234567D', 'ES', 'cif'));
        self::assertFalse($this->taxid->isValid('P12345674', 'ES', 'cif'));

        // A (limited company) must use the digit form.
        self::assertTrue($this->taxid->isValid('A58818501', 'ES', 'cif'));
        self::assertFalse($this->taxid->isValid('A5881850A', 'ES', 'cif'));

        // C (other) accepts either.
        self::assertTrue($this->taxid->isValid('C12345674', 'ES', 'cif'));
        self::assertTrue($this->taxid->isValid('C1234567D', 'ES', 'cif'));
    }

    /**
     * France's TVA key may be alphabetic, in which case only the SIREN behind
     * it can be verified. That must not become a silent "accept anything".
     */
    public function testFrenchAlphabeticTvaKeyStillEnforcesTheSiren(): void
    {
        self::assertTrue($this->taxid->isValid('XX552100554', 'FR', 'tva'));
        self::assertFalse($this->taxid->isValid('XX552100555', 'FR', 'tva'));
    }

    /**
     * La Poste's SIRETs famously fail Luhn and use a sum-of-digits rule instead.
     */
    public function testLaPosteSiretUsesItsOwnRule(): void
    {
        self::assertTrue($this->taxid->isValid('35600000000001', 'FR', 'siret'));
        self::assertFalse($this->taxid->isValid('35600000000002', 'FR', 'siret'));
    }

    /**
     * The UK ran out of numbers under the original modulus-97 rule and added the
     * "9755" variant. Both must be accepted, with no way to tell them apart.
     */
    public function testBritishVatAcceptsBothModulusGenerations(): void
    {
        self::assertTrue($this->taxid->isValid('123456782', 'GB', 'vat'), 'Original modulus-97 series');
        self::assertTrue($this->taxid->isValid('123456727', 'GB', 'vat'), 'The 9755 series');
    }

    /**
     * A twelve-digit UK VAT number carries a branch code that takes no part in
     * the checksum.
     */
    public function testBritishVatIgnoresTheBranchSuffix(): void
    {
        self::assertTrue($this->taxid->isValid('123456782001', 'GB', 'vat'));
    }

    /**
     * Monaco sits inside the French VAT territory and uses French numbers, so
     * the same value must validate under either country.
     */
    public function testMonacoUsesTheFrenchAlgorithms(): void
    {
        self::assertTrue($this->taxid->isValid('96552100554', 'MC', 'tva'));
        self::assertTrue($this->taxid->isValid('552100554', 'MC', 'siren'));
    }

    /**
     * Northern Ireland's XI numbers are GB registrations under another prefix.
     */
    public function testNorthernIrelandSharesTheBritishAlgorithm(): void
    {
        self::assertTrue($this->taxid->isValid('XI123456782'));
        self::assertSame('XI', $this->taxid->parse('XI123456782')->countryCode);
    }

    /**
     * Italy substitutes letters for digits when two people would collide
     * (omocodia); those codes are valid and must parse.
     */
    public function testItalianOmocodiaCodesAreAccepted(): void
    {
        $parsed = $this->taxid->tryParse('RSSMRA85T10A562S', 'IT');

        self::assertNotNull($parsed);
        self::assertSame('cf', $parsed->scheme);
    }

    /**
     * Belgium widened its VAT number from nine to ten digits by prefixing a
     * zero; the shorter form is still quoted.
     */
    public function testBelgianNineDigitVatIsStillAccepted(): void
    {
        self::assertTrue($this->taxid->isValid('776091951', 'BE', 'vat'));
    }

    /**
     * Chile's verifier is `K` when the modulus lands on 10 — the one place a
     * Latin-American tax ID is not purely numeric.
     */
    public function testChileanRutWithALetterVerifier(): void
    {
        // 12.345.670-K
        self::assertTrue($this->taxid->isValid('12345670K', 'CL', 'rut'));
        self::assertFalse($this->taxid->isValid('123456700', 'CL', 'rut'));
    }

    /**
     * Finland writes the century between the date and the serial: `+` for the
     * 1800s, `-` or U-Y for the 1900s, A-F for the 2000s. Normalization strips
     * the punctuation forms and keeps the letters, so the same person's identity
     * code legitimately arrives as ten characters or eleven. The checksum never
     * sees the marker, so both must validate.
     */
    public function testFinnishCenturyMarkerMayBeStrippedOrKept(): void
    {
        // 1900s, punctuation marker: stripped, ten characters.
        self::assertTrue($this->taxid->isValid('010190-123M', 'FI', 'henkilotunnus'));
        // 1800s, punctuation marker: stripped too — the reason `+` is a separator.
        self::assertTrue($this->taxid->isValid('010190+123M', 'FI', 'henkilotunnus'));
        // 2000s, letter marker: kept, eleven characters, same arithmetic.
        self::assertTrue($this->taxid->isValid('010106A123Y', 'FI', 'henkilotunnus'));

        // The marker is not a wildcard: a letter that marks no century is refused.
        self::assertFalse($this->taxid->isValid('010106G123Y', 'FI', 'henkilotunnus'));
    }

    /**
     * The control alphabet has 31 characters and omits G, I, O, Q and Z, which
     * are the ones a reader confuses with 6, 1, 0, 0 and 2. A value ending in
     * one of them is not a mistyped check character — it is not a henkilötunnus.
     */
    public function testFinnishControlAlphabetOmitsTheConfusableLetters(): void
    {
        self::assertTrue($this->taxid->isValid('290280123X', 'FI', 'henkilotunnus'));

        foreach (['G', 'I', 'O', 'Q', 'Z'] as $absent) {
            self::assertFalse(
                $this->taxid->isValid('290280123' . $absent, 'FI', 'henkilotunnus'),
                $absent . ' is not in the control alphabet and must not be accepted.',
            );
        }
    }

    /**
     * Hungary's personal tax identifier always starts with 8, and a serial whose
     * weighted sum leaves a remainder of ten is never issued — ten cannot be
     * written in the single digit the format allows, and the office skips the
     * serial rather than folding it to zero. Folding it would validate numbers
     * that do not exist.
     */
    public function testHungarianPersonalIdRejectsTheUnwritableRemainder(): void
    {
        self::assertTrue($this->taxid->isValid('8123456786', 'HU', 'adoazonosito'));

        // 800000013 has a weighted sum leaving remainder 10, so no check digit
        // completes it — every ending must be refused.
        for ($digit = 0; $digit <= 9; $digit++) {
            self::assertFalse(
                $this->taxid->isValid('800000013' . $digit, 'HU', 'adoazonosito'),
                'A serial whose remainder is 10 was accepted with check digit ' . $digit,
            );
        }
    }

    /**
     * Lithuania's leading digit encodes sex and century, and 9 marks a code
     * issued without a known birth date — for which the date fields carry no
     * meaning and must not be range-checked. `39013011237` is the case that
     * proves the check does something: its checksum is CORRECT and it is refused
     * purely because month 13 does not exist.
     */
    public function testLithuanianPersonalCodeChecksTheDateOnlyWhenThereIsOne(): void
    {
        self::assertTrue($this->taxid->isValid('39001011237', 'LT', 'asmens_kodas'));

        // Leading 9: no birth date, so nothing to validate but the checksum.
        self::assertTrue($this->taxid->isValid('90000001235', 'LT', 'asmens_kodas'));

        // Checksum correct, month impossible.
        self::assertFalse($this->taxid->isValid('39013011237', 'LT', 'asmens_kodas'));
    }
}
