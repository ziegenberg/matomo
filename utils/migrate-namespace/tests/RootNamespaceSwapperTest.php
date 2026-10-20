<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace\Tests;

use PHPUnit\Framework\TestCase;
use Utils\MigrateNamespace\Migrator;

/**
 * Fixture-driven tests for the root-namespace swap.
 *
 * Each fixture file under tests/Fixture contains the input code, a line with
 * only "-----", then the expected output. The separator consumes the input's
 * final newline, so the input half is restored by appending "\n"; the expected
 * half keeps the fixture file's own trailing newline.
 */
final class RootNamespaceSwapperTest extends TestCase
{
    private Migrator $migrator;

    protected function setUp(): void
    {
        $this->migrator = new Migrator();
    }

    /**
     * @dataProvider provideFixtures
     */
    public function testMigrate(string $name, string $input, string $expected): void
    {
        $this->assertSame($expected, $this->migrator->migrate($input), 'Fixture: ' . $name);
    }

    /**
     * @dataProvider provideFixtures
     */
    public function testIdempotency(string $name, string $input, string $expected): void
    {
        $this->assertSame($expected, $this->migrator->migrate($expected), 'Fixture: ' . $name);
    }

    public function testStringRawEscapingPreserved(): void
    {
        $input = "<?php\n\n\$a = 'Piwik\\\\Foo';\n";
        $expected = "<?php\n\n\$a = 'Matomo\\\\Foo';\n";
        $this->assertSame($expected, $this->migrator->migrate($input));

        $input = "<?php\n\n\$a = \"Piwik\\Foo\";\n";
        $expected = "<?php\n\n\$a = \"Matomo\\Foo\";\n";
        $this->assertSame($expected, $this->migrator->migrate($input));
    }

    public function testDocCommentSwappedOnlyWhenTextDiffers(): void
    {
        $input = <<<'PHP'
<?php

/**
 * @param \Piwik\Foo $foo
 */
function f($foo) {}
PHP;
        $expected = <<<'PHP'
<?php

/**
 * @param \Matomo\Foo $foo
 */
function f($foo) {}
PHP;
        $this->assertSame($expected, $this->migrator->migrate($input));

        $input = <<<'PHP'
<?php

/**
 * No Piwik here.
 */
function f() {}
PHP;
        $this->assertSame($input, $this->migrator->migrate($input));
    }

    /**
     * @return \Iterator<string, array{0: string, 1: string, 2: string}>
     */
    public static function provideFixtures(): \Iterator
    {
        $files = glob(__DIR__ . '/Fixture/*.php.inc') ?: [];

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
