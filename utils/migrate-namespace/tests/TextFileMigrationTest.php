<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace\Tests;

use PHPUnit\Framework\TestCase;
use Utils\MigrateNamespace\RootNamespace;

/**
 * Content-level tests for the shared swap rule over text files.
 *
 * Each fixture file under tests/FixtureText contains the input text, a line
 * with only "-----", then the expected output (same convention as the PHP-mode
 * fixtures). Only the pure byte rule is asserted here; file I/O (collection,
 * allowlist, second-run no-op) is covered in MigrationRunnerTest and
 * SourceCleanGuardTest.
 */
final class TextFileMigrationTest extends TestCase
{
    /**
     * @dataProvider provideFixtures
     */
    public function testSwap(string $name, string $input, string $expected): void
    {
        $this->assertSame($expected, RootNamespace::swapQualified($input), 'Fixture: ' . $name);
    }

    /**
     * @return \Iterator<string, array{0: string, 1: string, 2: string}>
     */
    public static function provideFixtures(): \Iterator
    {
        $files = glob(__DIR__ . '/FixtureText/*.inc') ?: [];

        foreach ($files as $file) {
            $content = file_get_contents($file);

            if ($content === false) {
                throw new \RuntimeException('Unreadable fixture: ' . $file);
            }

            $parts = explode("\n-----\n", $content, 2);

            if (count($parts) !== 2) {
                throw new \RuntimeException('Fixture must contain a "-----" separator line: ' . $file);
            }

            yield basename($file) => [basename($file), $parts[0] . "\n", $parts[1]];
        }
    }
}
