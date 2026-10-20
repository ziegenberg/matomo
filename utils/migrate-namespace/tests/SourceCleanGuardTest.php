<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace\Tests;

use PHPUnit\Framework\TestCase;
use Utils\MigrateNamespace\SourceCleanGuard;

final class SourceCleanGuardTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/matomo-migrate-namespace-guard-' . bin2hex(random_bytes(8));
        mkdir($this->dir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->dir);
    }

    public function testFlagsCodeReference(): void
    {
        $file = $this->write('Code.php', "<?php\n\nnamespace Piwik;\n\nclass A {}\n");

        $result = (new SourceCleanGuard())->scan([$this->dir]);

        $this->assertSame([$file], $result['violations']);
        $this->assertSame([], $result['failed']);
    }

    public function testFlagsDocblockReference(): void
    {
        $file = $this->write('Docblock.php', <<<'PHP'
<?php

/**
 * @param \Piwik\Foo $foo
 */
function f($foo) {}
PHP
        );

        $result = (new SourceCleanGuard())->scan([$this->dir]);

        $this->assertSame([$file], $result['violations']);
        $this->assertSame([], $result['failed']);
    }

    public function testFlagsStringReference(): void
    {
        $file = $this->write('String.php', "<?php\n\n\$a = 'Piwik\\\\Foo';\n");

        $result = (new SourceCleanGuard())->scan([$this->dir]);

        $this->assertSame([$file], $result['violations']);
        $this->assertSame([], $result['failed']);
    }

    public function testNoViolationForCommentsDisplayStringsAndConstants(): void
    {
        $this->write('Clean.php', <<<'PHP'
<?php

namespace Matomo;

/**
 * Canonical docblock.
 */
class A
{
    public function get()
    {
        $display = 'Piwik';
        define('PIWIK_CONSTANT', 1);
        return PIWIK_CONSTANT;
    }
}

// This comment mentions Piwik\Foo.
/* Piwik\Foo in a block comment */
PHP
        );

        $result = (new SourceCleanGuard())->scan([$this->dir]);

        $this->assertSame([], $result['violations']);
        $this->assertSame([], $result['failed']);
    }

    public function testAllowlistPrefixSilencesViolation(): void
    {
        $file = $this->write('Allowed.php', "<?php\n\nnamespace Piwik;\n\nclass A {}\n");

        $result = (new SourceCleanGuard())->scan([$this->dir], [$file]);

        $this->assertSame([], $result['violations']);
        $this->assertSame([], $result['failed']);
    }

    public function testRepoRelativeAllowlistPrefixDoesNotMatchOutsideRepo(): void
    {
        $file = $this->write('Relative.php', "<?php\n\nnamespace Piwik;\n\nclass A {}\n");

        // The temp tree is outside the repository, so the repo-relative prefix
        // cannot match; the absolute prefix must be used instead. This guards
        // the prefix-matching helper against matching unrelated absolute paths.
        $result = (new SourceCleanGuard())->scan([$this->dir], ['tests/PHPUnit/Unit/Relative.php']);

        $this->assertSame([$file], $result['violations']);
    }

    public function testParseErrorLandsInFailed(): void
    {
        $this->write('Broken.php', "<?php\n\nclass {\n");

        $result = (new SourceCleanGuard())->scan([$this->dir]);

        $this->assertSame([], $result['violations']);
        $this->assertCount(1, $result['failed']);
        $this->assertStringContainsString('Broken.php', $result['failed'][0]);
    }

    public function testMissingPathLandsInFailed(): void
    {
        $missing = $this->dir . '/missing.php';

        $result = (new SourceCleanGuard())->scan([$missing]);

        $this->assertSame([], $result['violations']);
        $this->assertCount(1, $result['failed']);
        $this->assertStringContainsString('missing.php', $result['failed'][0]);
    }

    public function testCoreTreeIsCleanWhenEnabled(): void
    {
        if (getenv('MIGRATE_NAMESPACE_GUARD_CORE') !== '1') {
            $this->markTestSkipped(
                'Set MIGRATE_NAMESPACE_GUARD_CORE=1 to run the real-tree core check (issues 05-07 flip it on).'
            );
        }

        $root = dirname(__DIR__, 3);
        $result = (new SourceCleanGuard())->scan(
            [$root . '/core', $root . '/plugins', $root . '/tests'],
            SourceCleanGuard::defaultAllowlist()
        );

        $this->assertSame([], $result['violations'], 'Core tree must be source-clean against the default allowlist.');
        $this->assertSame([], $result['failed']);
    }

    private function write(string $name, string $content): string
    {
        $path = $this->dir . '/' . $name;
        file_put_contents($path, $content);

        return $path;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getPathname());
            } else {
                unlink($fileInfo->getPathname());
            }
        }

        rmdir($dir);
    }
}
