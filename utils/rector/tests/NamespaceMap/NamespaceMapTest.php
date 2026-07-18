<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\NamespaceMap;

use PHPUnit\Framework\TestCase;
use Utils\Rector\Namespace\RootNamespace;
use Utils\Rector\NamespaceMap\NamespaceMap;

/**
 * @covers \Utils\Rector\NamespaceMap\NamespaceMap
 *
 * The map must be complete whether the scanned source is still pre-rename
 * (Piwik\ declarations) or already post-rename (Matomo\ declarations), so the
 * migration is re-runnable: a rector pass that rebuilds the map from
 * already-renamed source still maps every class. The facade (whose short name
 * changes) is handled in both directions.
 */
final class NamespaceMapTest extends TestCase
{
    /** @var string */
    private $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/nsmap-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/core', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
    }

    /**
     * Pre-rename source: a Piwik\X declaration maps Piwik\X => Matomo\X, and the
     * facade Piwik\Piwik maps to Matomo\Matomo.
     */
    public function test_maps_pre_rename_piwik_declarations(): void
    {
        $this->write('core/Period.php', '<?php namespace Piwik; class Period {}');
        $this->write('core/Db.php', '<?php namespace Piwik; class Db {}');
        $this->write('core/Piwik.php', '<?php namespace Piwik; class Piwik {}');

        $map = NamespaceMap::fromPrefixPaths([RootNamespace::OLD_PREFIX => [$this->root . '/core']])->toArray();

        self::assertSame('Matomo\\Period', $map['Piwik\\Period']);
        self::assertSame('Matomo\\Db', $map['Piwik\\Db']);
        self::assertSame(RootNamespace::FACADE_NEW, $map[RootNamespace::FACADE_OLD]);
    }

    /**
     * Post-rename source: a Matomo\X declaration reverse-derives the same
     * Piwik\X => Matomo\X entry, so the map is complete after a completed rename.
     * The facade Matomo\Matomo reverses to Piwik\Piwik, not Piwik\Matomo.
     */
    public function test_reverse_derives_from_post_rename_matomo_declarations(): void
    {
        $this->write('core/Period.php', '<?php namespace Matomo; class Period {}');
        $this->write('core/Db.php', '<?php namespace Matomo; class Db {}');
        $this->write('core/Matomo.php', '<?php namespace Matomo; class Matomo {}');

        $map = NamespaceMap::fromPrefixPaths([RootNamespace::OLD_PREFIX => [$this->root . '/core']])->toArray();

        self::assertSame('Matomo\\Period', $map['Piwik\\Period']);
        self::assertSame('Matomo\\Db', $map['Piwik\\Db']);
        self::assertSame(RootNamespace::FACADE_NEW, $map[RootNamespace::FACADE_OLD]);
        self::assertArrayNotHasKey('Piwik\\Matomo', $map);
    }

    /**
     * A partial rename (some files still Piwik\, some already Matomo\) yields a
     * complete map with no conflicts: both forms produce the same entry.
     */
    public function test_handles_partial_rename_without_conflicts(): void
    {
        $this->write('core/Period.php', '<?php namespace Piwik; class Period {}');
        $this->write('core/Db.php', '<?php namespace Matomo; class Db {}');

        $map = NamespaceMap::fromPrefixPaths([RootNamespace::OLD_PREFIX => [$this->root . '/core']])->toArray();

        self::assertSame('Matomo\\Period', $map['Piwik\\Period']);
        self::assertSame('Matomo\\Db', $map['Piwik\\Db']);
    }

    /**
     * Non-PSR-4-conforming files (multiple class-likes per file, or a class name
     * not matching the file name) are still collected.
     */
    public function test_collects_multiple_class_likes_per_file(): void
    {
        $this->write('core/Widgets.php', '<?php namespace Piwik; class WidgetOne {} class WidgetTwo {} interface WidgetContract {}');

        $map = NamespaceMap::fromPrefixPaths([RootNamespace::OLD_PREFIX => [$this->root . '/core']])->toArray();

        self::assertSame('Matomo\\WidgetOne', $map['Piwik\\WidgetOne']);
        self::assertSame('Matomo\\WidgetTwo', $map['Piwik\\WidgetTwo']);
        self::assertSame('Matomo\\WidgetContract', $map['Piwik\\WidgetContract']);
    }

    /**
     * Foreign namespaces (Utils\Rector\..., a bare root with no Piwik\/Matomo\
     * prefix) are never mapped.
     */
    public function test_ignores_foreign_namespaces(): void
    {
        $this->write('core/Period.php', '<?php namespace Piwik; class Period {}');
        $this->write('core/Foreign.php', '<?php namespace Utils\Rector; class Foreign {}');

        $map = NamespaceMap::fromPrefixPaths([RootNamespace::OLD_PREFIX => [$this->root . '/core']])->toArray();

        self::assertSame('Matomo\\Period', $map['Piwik\\Period']);
        self::assertArrayNotHasKey('Piwik\\Foreign', $map);
        self::assertArrayNotHasKey('Matomo\\Foreign', $map);
    }

    private function write(string $relativePath, string $content): void
    {
        file_put_contents($this->root . '/' . $relativePath, $content);
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $full = $path . '/' . $entry;
            if (is_dir($full)) {
                $this->removeTree($full);
            } else {
                unlink($full);
            }
        }

        rmdir($path);
    }
}
