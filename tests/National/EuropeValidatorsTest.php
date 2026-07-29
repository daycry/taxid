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
        yield 'LU matricule' => [
            'LU',
            'matricule',
            ['1990010112385', '1985031200757'],
            ['1990010112386', '1990010112395', '199001011238'],
        ];
        yield 'AT UID' => ['AT', 'uid', ['U10223006'], ['U10223007']];
        yield 'AT Steuernummer' => ['AT', 'steuernummer', ['123456782', '010190007'], ['123456783', '12345678', '1234567820']];
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

        // The three date-invalid values all carry a CORRECT check digit, so each
        // one can only be refused by looking at the encoded date.
        yield 'SK rodné číslo' => [
            'SK',
            'rodne_cislo',
            ['9001011239', '9051011233', '500101123'],
            ['9001011238', '9013011238', '9001321230', '9063011232', '90010112'],
        ];

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
        yield 'EE isikukood' => [
            'EE',
            'isikukood',
            [
                '37605030299',
                // Same second-pass and fold-to-zero gap as LT asmens kodas — the
                // two classes share the algorithm verbatim (see
                // EeIsikukoodValidator's docblock), so the exposure is doubled.
                '30001010187',
                '10001010080',
            ],
            ['37605030298'],
        ];

        yield 'LV VAT'           => ['LV', 'vat', ['40003009497'], ['40003009498']];
        yield 'LV personas kods' => ['LV', 'personas_kods', ['32081012341'], ['32081012342']];

        yield 'LT PVM' => ['LT', 'pvm', ['213179412'], ['213179413', '213179422']];
        yield 'LT asmens kodas' => [
            'LT',
            'asmens_kodas',
            [
                '39001011237', '49001011238', '90000001235',
                // First-pass remainder 10: forces WEIGHTS_SECOND, which no other
                // value in the suite reaches.
                '30001010187',
                // First-pass remainder 10, second pass ALSO 10: exercises the
                // fold to 0, otherwise untested in the whole suite.
                '10001010080',
            ],
            ['39001011238', '79001011237', '39013011237', '3900101123'],
        ];
        yield 'MT VAT' => ['MT', 'vat', ['12345634'], ['12345635']];
        yield 'MT ID card' => ['MT', 'id_card', ['0123456M', '0000123G'], ['0123456K', '01234567', '123456M']];
        yield 'MT TIN'     => ['MT', 'tin', ['123456789'], ['12345678', '1234567890', '12345678A']];
        yield 'CY VAT' => ['CY', 'vat', ['10259033P'], ['10259033Q']];

        yield 'NO orgnr' => ['NO', 'orgnr', ['974760673'], ['974760674']];
        yield 'NO MVA'   => ['NO', 'mva', ['974760673MVA'], ['974760674MVA']];
        yield 'NO fødselsnummer' => [
            'NO',
            'fodselsnummer',
            ['01019012480', '41019012393'],
            ['01419012382', '01019012481', '0101901248'],
        ];

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
     * Ireland's legacy VAT "individual style" allows a `+` or `*` as its second
     * character (`1+23456T`), but `Normalizer` treats `+` as a separator, so it
     * is stripped before any pattern sees the value — `1+23456T` becomes the
     * unmatchable seven-character `123456T`.
     *
     * This is deliberate, not a regression to "fix": the form was already
     * unreachable before `+` became a separator, since it was refused upstream
     * by the `IllegalCharacters` gate. Only the reported violation code
     * changed, from `illegal_characters` to `bad_length` — no value that used
     * to validate stopped validating. Exempting `+` from stripping inside an
     * Irish value would need registry-aware normalization, which `Normalizer`
     * deliberately does not have (see its class docblock); "fixing" this here
     * would reopen a form nothing else in the registry recognises anyway.
     */
    public function testIrishLegacyIndividualVatWithPlusIsUnreachable(): void
    {
        $result = $this->taxid->validate('1+23456T', 'IE', 'vat');

        self::assertFalse($result->isValid());
        self::assertSame('bad_length', $result->errorCode());
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

    /**
     * Luxembourg's matricule carries two check digits over the same eleven-digit
     * base: a Luhn digit in the twelfth position and a Verhoeff digit in the
     * thirteenth. Each invalid value here breaks exactly one of them, so an
     * implementation that checks only one digit — or that checks the same digit
     * twice — fails this test and passes everything else.
     */
    public function testLuxembourgMatriculeChecksBothDigitsIndependently(): void
    {
        self::assertTrue($this->taxid->isValid('1990010112385', 'LU', 'matricule'));

        // Luhn digit right, Verhoeff digit wrong.
        self::assertFalse($this->taxid->isValid('1990010112386', 'LU', 'matricule'));

        // Verhoeff digit right, Luhn digit wrong.
        self::assertFalse($this->taxid->isValid('1990010112395', 'LU', 'matricule'));
    }

    /**
     * Malta issues two different TINs to individuals, and both are structure
     * only. A national's is their identity-card number, seven digits and a
     * category letter — the letter is a category, not a check character, so it
     * must be one of the eight that exist and nothing more can be verified. A
     * non-national gets nine digits with nothing checkable at all.
     *
     * Leading zeros are part of the identity-card number. The short form 999L is
     * the same value unpadded, and the padded form is what any system must store.
     */
    public function testMalteseIdentifiersAreStructureOnly(): void
    {
        self::assertTrue($this->taxid->isValid('0000123G', 'MT', 'id_card'));
        self::assertFalse($this->taxid->isValid('123G', 'MT', 'id_card'), 'The unpadded short form is not the storable form.');

        foreach (['A', 'B', 'G', 'H', 'L', 'M', 'P', 'Z'] as $category) {
            self::assertTrue(
                $this->taxid->isValid('0123456' . $category, 'MT', 'id_card'),
                $category . ' is a real category letter and must be accepted.',
            );
        }

        foreach (['C', 'D', 'K', 'X'] as $absent) {
            self::assertFalse(
                $this->taxid->isValid('0123456' . $absent, 'MT', 'id_card'),
                $absent . ' is not a category letter and must be refused.',
            );
        }

        // Nine digits is the non-national TIN and must not be read as an id_card.
        self::assertTrue($this->taxid->isValid('123456789', 'MT', 'tin'));
        self::assertFalse($this->taxid->isValid('123456789', 'MT', 'id_card'));
    }

    /**
     * Norway offsets a field by 40 for two different purposes, and only one of
     * them is a tax identifier. A D-number, issued to non-residents, adds 40 to
     * the DAY and is a real fødselsnummer. An H-number adds 40 to the MONTH and
     * exists only inside the health service.
     *
     * `01419012382` is the case that matters: its checksum is entirely valid, so
     * it can only be refused by looking at the month. A validator that checks
     * arithmetic alone accepts it.
     */
    public function testNorwegianDNumbersAreAcceptedAndHNumbersAreNot(): void
    {
        self::assertTrue($this->taxid->isValid('01019012480', 'NO', 'fodselsnummer'));

        // D-number: day 01 + 40 = 41.
        self::assertTrue($this->taxid->isValid('41019012393', 'NO', 'fodselsnummer'));

        // H-number: month 01 + 40 = 41. Checksum valid, still not a tax ID.
        self::assertFalse($this->taxid->isValid('01419012382', 'NO', 'fodselsnummer'));
    }

    /**
     * Czechia and Slovakia both kept the Czechoslovak rodné číslo after 1993, so
     * one value is a valid personal identifier in either country. Before Slovakia
     * had a scheme for it, the same number validated as `CZ` and was refused as
     * `SK` with `check_digit_failed` — a misleading code, since the checksum was
     * fine and only the VAT category constraint had rejected it.
     *
     * This test is the pin against that asymmetry returning.
     */
    public function testTheCzechoslovakBirthNumberValidatesInBothSuccessorStates(): void
    {
        foreach (['7103191238', '9001011239'] as $tenDigits) {
            self::assertTrue($this->taxid->isValid($tenDigits, 'CZ'), 'CZ should accept ' . $tenDigits);
            self::assertTrue($this->taxid->isValid($tenDigits, 'SK'), 'SK should accept ' . $tenDigits);
        }

        // The nine-digit pre-1954 form, likewise.
        self::assertTrue($this->taxid->isValid('500101123', 'CZ'));
        self::assertTrue($this->taxid->isValid('500101123', 'SK'));
    }

    /**
     * Slovakia's DIČ and its rodné číslo are different numbers that both take ten
     * digits, so they overlap — which the Validator handles by trying every
     * matching scheme. What must not happen is either one swallowing the other:
     * a DIČ has to keep reading as `vat`, and a birth number as `rodne_cislo`.
     */
    public function testSlovakTenDigitSchemesDoNotShadowEachOther(): void
    {
        self::assertSame('vat', $this->taxid->parse('2020269922', 'SK')->scheme);
        self::assertSame('rodne_cislo', $this->taxid->parse('9001011239', 'SK')->scheme);

        // Demanding the wrong reading of either must fail rather than fall through.
        self::assertFalse($this->taxid->isValid('2020269922', 'SK', 'rodne_cislo'));
        self::assertFalse($this->taxid->isValid('9001011239', 'SK', 'vat'));
    }

    /**
     * The month field carries the sex: women's numbers add 50, and since 2004 a
     * further 20 when a day's serials run out. So 51 is January and must be
     * accepted, while 63 reduces to month 13 and must not be — both values below
     * carry a correct check digit, so only the date handling separates them.
     */
    public function testSlovakBirthNumberMonthOffsetsAreDecodedNotIgnored(): void
    {
        self::assertTrue($this->taxid->isValid('9051011233', 'SK', 'rodne_cislo'));
        self::assertFalse($this->taxid->isValid('9063011232', 'SK', 'rodne_cislo'));
    }
}
