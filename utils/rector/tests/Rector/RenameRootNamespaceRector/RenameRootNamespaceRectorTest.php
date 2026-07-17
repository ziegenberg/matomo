<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\Rector\RenameRootNamespaceRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

/**
 * @covers \Utils\Rector\Rector\RenameRootNamespaceRector
 *
 * The custom String_ rule rewrites the dynamic FQCN string forms the built-in
 * rules cannot reach (sprintf / interpolated templates, prefix fragments, prefix
 * sentinels) and skips exact class-name strings (delegated to RenameStringRector,
 * so the facade Piwik\Piwik -> Matomo\Matomo is handled by the map) and
 * non-namespace strings (display text, URLs, translation keys, serialize payloads).
 */
final class RenameRootNamespaceRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData
     */
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    /**
     * @return \Iterator<string, array{0: string}>
     */
    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
