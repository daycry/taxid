<?php

declare(strict_types=1);

namespace Tests\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Architecture rule: the framework-free core must stay free of CodeIgniter 4.
 *
 * The whole promise of this package's lower layer is that it validates a tax ID
 * with zero dependencies — usable from a Symfony app, a Laravel job, or a bare
 * script. That promise is one careless `use CodeIgniter\…` away from being
 * false, and nobody notices until a consumer files an issue. So it is a test,
 * not a convention.
 *
 * Guarded wholesale: `Contracts`, `Core` (including `Core/Checksum`), `DTO`,
 * `Enums`, `Exceptions`, `Registry`, `National` and `Resolver`.
 *
 * Guarded individually, because they sit next to files that legitimately DO
 * depend on CI4: `src/TaxId.php` (the standalone facade),
 * `Providers/NullProvider.php` and `Providers/ChainProvider.php`.
 * `Providers/` as a directory is deliberately NOT guarded —
 * `DatabaseProvider`, `CachedProvider` and `ViesProvider` live there and are
 * adapters by design.
 *
 * Two self-tests at the bottom prove the detector isn't a stub that would pass
 * green no matter what.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §3
 */
final class CoreIsFrameworkFreeTest extends TestCase
{
    /** @var string[] */
    private const array GUARDED_DIRECTORIES = [
        'Contracts',
        'Core',
        'DTO',
        'Enums',
        'Exceptions',
        'National',
        'Registry',
        'Resolver',
    ];

    /** @var string[] */
    private const array GUARDED_FILES = [
        'TaxId.php',
        'Providers/NullProvider.php',
        'Providers/ChainProvider.php',
    ];

    /** @var string[] */
    private const array FORBIDDEN_NEEDLES = [
        'CodeIgniter\\',
        'codeigniter4',
    ];

    public function testGuardedDirectoriesDoNotReferenceCodeIgniter(): void
    {
        $violations = [];

        foreach (self::GUARDED_DIRECTORIES as $directory) {
            $path = dirname(__DIR__, 2) . '/src/' . $directory;

            self::assertDirectoryExists($path, 'Guarded directory is missing: ' . $directory);

            foreach ($this->phpFilesIn($path) as $file) {
                $contents = file_get_contents($file->getPathname());

                if ($contents === false) {
                    continue;
                }

                if (self::containsFrameworkReference($contents)) {
                    $violations[] = $file->getPathname() . ' references CodeIgniter';
                }
            }
        }

        self::assertSame(
            [],
            $violations,
            'The framework-free core (' . implode(', ', self::GUARDED_DIRECTORIES)
                . ") must not reference CodeIgniter\\ / codeigniter4:\n" . implode("\n", $violations),
        );
    }

    public function testGuardedFilesDoNotReferenceCodeIgniter(): void
    {
        $violations = [];

        foreach (self::GUARDED_FILES as $relativeFile) {
            $path = dirname(__DIR__, 2) . '/src/' . $relativeFile;

            if (! is_file($path)) {
                $violations[] = $path . ' does not exist';

                continue;
            }

            $contents = file_get_contents($path);

            if ($contents === false) {
                continue;
            }

            if (self::containsFrameworkReference($contents)) {
                $violations[] = $path . ' references CodeIgniter';
            }
        }

        self::assertSame(
            [],
            $violations,
            'The individually-guarded files (' . implode(', ', self::GUARDED_FILES)
                . ") must not reference CodeIgniter\\ / codeigniter4:\n" . implode("\n", $violations),
        );
    }

    /**
     * The registry data file pulls in every bundled validator by `::class`, so
     * it belongs to the framework-free layer too — and it is the one file most
     * likely to grow a stray dependency when a country is added.
     */
    public function testRegistryDataIsFrameworkFree(): void
    {
        $path     = dirname(__DIR__, 2) . '/src/Registry/data/countries.php';
        $contents = file_get_contents($path);

        self::assertIsString($contents, 'Could not read the registry data file.');
        self::assertFalse(
            self::containsFrameworkReference($contents),
            'src/Registry/data/countries.php must not reference CodeIgniter.',
        );
    }

    /**
     * Negative self-test: proves the detector actually detects, rather than
     * quietly returning false for everything.
     */
    public function testDetectorFlagsSourceContainingCodeIgniterReference(): void
    {
        $dirty = <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Fixtures;

            use CodeIgniter\Config\BaseConfig;

            final class TaintedConfig extends BaseConfig
            {
            }
            PHP;

        self::assertTrue(
            self::containsFrameworkReference($dirty),
            'Expected the detector to flag source referencing CodeIgniter\\Config\\BaseConfig',
        );
    }

    /**
     * Companion self-test: proves it isn't an "always true" stub either.
     */
    public function testDetectorDoesNotFlagCleanSource(): void
    {
        $clean = <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Fixtures;

            final class PlainValueObject
            {
                public function __construct(private readonly string $value)
                {
                }
            }
            PHP;

        self::assertFalse(
            self::containsFrameworkReference($clean),
            'Did not expect the detector to flag framework-free source',
        );
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function phpFilesIn(string $path): iterable
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                yield $file;
            }
        }
    }

    private static function containsFrameworkReference(string $phpSource): bool
    {
        foreach (self::FORBIDDEN_NEEDLES as $needle) {
            if (str_contains($phpSource, $needle)) {
                return true;
            }
        }

        return false;
    }
}
