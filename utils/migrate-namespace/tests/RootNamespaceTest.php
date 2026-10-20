<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace\Tests;

use PHPUnit\Framework\TestCase;
use Utils\MigrateNamespace\RootNamespace;

/**
 * Unit tests for the value-aware swap rule used by strings, docblocks, and
 * raw-literal preservation.
 */
final class RootNamespaceTest extends TestCase
{
    /**
     * @dataProvider provideSwapQualified
     */
    public function testSwapQualified(string $input, string $expected): void
    {
        $this->assertSame($expected, RootNamespace::swapQualified($input));
    }

    /**
     * @return \Iterator<string, array{0: string, 1: string}>
     */
    public static function provideSwapQualified(): \Iterator
    {
        yield 'facade both segments' => ['Piwik\Piwik', 'Matomo\Matomo'];
        yield 'facade in fully qualified position' => ['\Piwik\Piwik::getInstance()', '\Matomo\Matomo::getInstance()'];
        yield 'fully qualified root' => ['\Piwik\Foo', '\Matomo\Foo'];
        yield 'multi segment root' => ['Piwik\Foo\Bar', 'Matomo\Foo\Bar'];
        yield 'interior facade segment untouched' => ['Piwik\Piwik\Foo', 'Matomo\Piwik\Foo'];
        yield 'regex-escaped root' => ['Piwik\\Plugins', 'Matomo\\Plugins'];
        yield 'regex-escaped facade' => ['Piwik\\Piwik', 'Matomo\\Matomo'];
        yield 'trailing backslash run' => ['Piwik\DataTable\Filter\\', 'Matomo\DataTable\Filter\\'];
        yield 'facade with underscore suffix is not facade' => ['Piwik\Piwik_Key', 'Matomo\Piwik_Key'];
        yield 'facade with identifier suffix is not facade' => ['Piwik\Piwikx', 'Matomo\Piwikx'];
        yield 'identifier before backslash blocks swap' => ['x\Piwik\Foo', 'x\Piwik\Foo'];
        yield 'identifier prefix blocks swap' => ['xPiwik\Foo', 'xPiwik\Foo'];
        yield 'short name with namespace suffix untouched' => ['PiwikFoo\Bar', 'PiwikFoo\Bar'];
        yield 'bare root untouched' => ['Piwik', 'Piwik'];
        yield 'underscore suffix untouched' => ['Piwik_Key', 'Piwik_Key'];
        yield 'url untouched' => ['https://piwik.org', 'https://piwik.org'];
        yield 'uppercase constant untouched' => ['PIWIK_INCLUDE_PATH', 'PIWIK_INCLUDE_PATH'];
        yield 'case sensitive' => ['piwik\Foo', 'piwik\Foo'];
        yield 'canonical text unchanged' => ['Matomo\Foo', 'Matomo\Foo'];
        yield 'empty text' => ['', ''];
        yield 'multiple occurrences' => ['Piwik\Foo and Piwik\Bar', 'Matomo\Foo and Matomo\Bar'];
        yield 'triple facade segment untouched twice' => ['Piwik\Piwik\Piwik', 'Matomo\Piwik\Piwik'];
    }

    /**
     * @dataProvider provideSwapRawLiteral
     */
    public function testSwapRawLiteral(string $raw, string $value, ?string $expected): void
    {
        $this->assertSame($expected, RootNamespace::swapRawLiteral($raw, $value));
    }

    /**
     * @return \Iterator<string, array{0: string, 1: string, 2: ?string}>
     */
    public static function provideSwapRawLiteral(): \Iterator
    {
        yield 'single-quoted doubled backslashes' => ["'Piwik\\\\Foo'", 'Piwik\Foo', "'Matomo\\\\Foo'"];
        yield 'double-quoted literal backslash' => ['"Piwik\Foo"', 'Piwik\Foo', '"Matomo\Foo"'];
        yield 'regex-escaped facade' => ["'Piwik\\\\Piwik'", 'Piwik\Piwik', "'Matomo\\\\Matomo'"];
        yield 'escape sequence after unqualified root stays' => [
            '"Piwik\Foo\nPiwik\t"',
            "Piwik\Foo\nPiwik\t",
            '"Matomo\Foo\nPiwik\t"',
        ];
        yield 'unpair-able raw falls back to null' => ['"\x50iwik\Foo"', 'Piwik\Foo', null];
    }
}
