<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace\Tests;

use PHPUnit\Framework\TestCase;
use Utils\MigrateNamespace\MigrationRunner;

final class MigrationRunnerTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/matomo-migrate-namespace-' . bin2hex(random_bytes(8));
        mkdir($this->dir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->dir);
    }

    public function testRunRewritesOnlyChangedFiles(): void
    {
        $changed = $this->dir . '/Changed.php';
        $unchanged = $this->dir . '/Unchanged.php';

        $changedSource = "<?php\n\nnamespace Piwik;\n\nclass A {}\n";
        $unchangedSource = "<?php\n\nnamespace Matomo;\n\nclass B {}\n";

        file_put_contents($changed, $changedSource);
        file_put_contents($unchanged, $unchangedSource);

        [$stdout, $stderr] = $this->streams();
        $exit = (new MigrationRunner(null, $stdout, $stderr))->run([$this->dir]);

        $this->assertSame(0, $exit);
        $this->assertSame("<?php\n\nnamespace Matomo;\n\nclass A {}\n", file_get_contents($changed));
        $this->assertSame($unchangedSource, file_get_contents($unchanged));
        $this->assertStringContainsString('changed: ' . $changed, $this->streamContents($stdout));
        $this->assertStringNotContainsString('changed: ' . $unchanged, $this->streamContents($stdout));
        $this->assertStringContainsString('1 changed, 1 unchanged, 0 failed', $this->streamContents($stdout));
    }

    public function testParseErrorReportsFailureAndContinues(): void
    {
        $broken = $this->dir . '/Broken.php';
        $good = $this->dir . '/Good.php';
        $unchanged = $this->dir . '/Unchanged.php';

        file_put_contents($broken, "<?php\n\nclass {\n");
        file_put_contents($good, "<?php\n\nnamespace Piwik;\n\nclass C {}\n");
        file_put_contents($unchanged, "<?php\n\nnamespace Matomo;\n\nclass D {}\n");

        [$stdout, $stderr] = $this->streams();
        $exit = (new MigrationRunner(null, $stdout, $stderr))->run([$this->dir]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('ERROR: ' . $broken, $this->streamContents($stderr));
        $this->assertStringContainsString('1 changed, 1 unchanged, 1 failed', $this->streamContents($stdout));
        // The parse failure must not stop the run.
        $this->assertStringContainsString('changed: ' . $good, $this->streamContents($stdout));
        $this->assertStringContainsString('namespace Matomo;', (string) file_get_contents($good));
    }

    public function testDryRunWritesNothing(): void
    {
        $changed = $this->dir . '/Changed.php';
        file_put_contents($changed, "<?php\n\nnamespace Piwik;\n\nclass A {}\n");

        [$stdout, $stderr] = $this->streams();
        $exit = (new MigrationRunner(null, $stdout, $stderr))->run([$this->dir], true);

        $this->assertSame(0, $exit);
        $this->assertSame("<?php\n\nnamespace Piwik;\n\nclass A {}\n", file_get_contents($changed));
        $this->assertStringContainsString('would change: ' . $changed, $this->streamContents($stdout));
        $this->assertStringContainsString('1 would change, 0 unchanged, 0 failed', $this->streamContents($stdout));
    }

    public function testSecondRunIsNoOp(): void
    {
        $changed = $this->dir . '/Changed.php';
        file_put_contents($changed, "<?php\n\nnamespace Piwik;\n\nclass A {}\n");

        [$stdout, $stderr] = $this->streams();
        $runner = new MigrationRunner(null, $stdout, $stderr);

        $this->assertSame(0, $runner->run([$this->dir]));
        $firstRunOutput = $this->streamContents($stdout);
        $this->assertStringContainsString('1 changed, 0 unchanged, 0 failed', $firstRunOutput);

        [$stdout2, $stderr2] = $this->streams();
        $runner2 = new MigrationRunner(null, $stdout2, $stderr2);

        $this->assertSame(0, $runner2->run([$this->dir]));
        $secondRunOutput = $this->streamContents($stdout2);
        $this->assertStringContainsString('0 changed, 1 unchanged, 0 failed', $secondRunOutput);
        $this->assertStringNotContainsString('changed: ', $secondRunOutput);
        $this->assertSame("<?php\n\nnamespace Matomo;\n\nclass A {}\n", file_get_contents($changed));
    }

    public function testNoOpOverCanonicalTree(): void
    {
        $canonical = $this->dir . '/Canonical.php';
        $source = <<<'PHP'
<?php

namespace Matomo;

/**
 * @param \Matomo\Foo $foo
 */
class A
{
    /** @var Matomo\DataTable */
    public $table;

    public function get()
    {
        return 'Matomo\Foo';
    }
}
PHP;
        file_put_contents($canonical, $source);

        [$stdout, $stderr] = $this->streams();
        $exit = (new MigrationRunner(null, $stdout, $stderr))->run([$this->dir]);

        $this->assertSame(0, $exit);
        $this->assertSame($source, file_get_contents($canonical));
        $this->assertStringContainsString('0 changed, 1 unchanged, 0 failed', $this->streamContents($stdout));
    }

    public function testRunSkipsAllowlistedFiles(): void
    {
        $allowed = $this->dir . '/Allowed.php';
        $other = $this->dir . '/Other.php';

        file_put_contents($allowed, "<?php\n\nnamespace Piwik;\n\nclass A {}\n");
        file_put_contents($other, "<?php\n\nnamespace Piwik;\n\nclass B {}\n");

        [$stdout, $stderr] = $this->streams();
        $exit = (new MigrationRunner(null, $stdout, $stderr))->run([$this->dir], false, [$allowed]);

        $this->assertSame(0, $exit);
        $this->assertSame("<?php\n\nnamespace Piwik;\n\nclass A {}\n", file_get_contents($allowed));
        $this->assertStringContainsString('namespace Matomo;', (string) file_get_contents($other));
        $this->assertStringContainsString('1 changed, 1 unchanged, 0 failed', $this->streamContents($stdout));
        $this->assertStringNotContainsString('changed: ' . $allowed, $this->streamContents($stdout));
    }

    public function testTextFileModeSwapsTextExtensions(): void
    {
        $neon = $this->dir . '/Config.neon';
        $sh = $this->dir . '/build.sh';
        $json = $this->dir . '/composer.json';
        $txt = $this->dir . '/notes.txt';

        file_put_contents($neon, "# Piwik\\Log\\Logger is a proxy\n");
        file_put_contents($sh, "namespace Piwik;\n");
        file_put_contents($json, "{\"psr-4\": {\"Piwik\\\\Plugins\\\\\": \"plugins/\"}}\n");
        file_put_contents($txt, "Piwik\\Foo stays\n");

        [$stdout, $stderr] = $this->streams();
        $exit = (new MigrationRunner(null, $stdout, $stderr))->run([$this->dir], false, [], ['neon', 'sh', 'json']);

        $this->assertSame(0, $exit);
        $this->assertSame("# Matomo\\Log\\Logger is a proxy\n", file_get_contents($neon));
        $this->assertSame("namespace Matomo;\n", file_get_contents($sh));
        $this->assertSame("{\"psr-4\": {\"Matomo\\\\Plugins\\\\\": \"plugins/\"}}\n", file_get_contents($json));
        $this->assertSame("Piwik\\Foo stays\n", file_get_contents($txt));
        $this->assertStringContainsString('3 changed, 0 unchanged, 0 failed', $this->streamContents($stdout));
    }

    public function testTextFileModeRespectsAllowlist(): void
    {
        $allowed = $this->dir . '/Allowed.neon';
        $other = $this->dir . '/Other.neon';

        file_put_contents($allowed, "# Piwik\\Log\\Logger\n");
        file_put_contents($other, "# Piwik\\Log\\Logger\n");

        [$stdout, $stderr] = $this->streams();
        $exit = (new MigrationRunner(null, $stdout, $stderr))->run([$this->dir], false, [$allowed], ['neon']);

        $this->assertSame(0, $exit);
        $this->assertSame("# Piwik\\Log\\Logger\n", file_get_contents($allowed));
        $this->assertSame("# Matomo\\Log\\Logger\n", file_get_contents($other));
        $this->assertStringContainsString('1 changed, 1 unchanged, 0 failed', $this->streamContents($stdout));
    }

    public function testTextFileModeSecondRunNoOp(): void
    {
        $neon = $this->dir . '/Config.neon';
        file_put_contents($neon, "# Piwik\\Log\\Logger\n");

        [$stdout, $stderr] = $this->streams();
        $runner = new MigrationRunner(null, $stdout, $stderr);

        $this->assertSame(0, $runner->run([$this->dir], false, [], ['neon']));
        $this->assertStringContainsString('1 changed, 0 unchanged, 0 failed', $this->streamContents($stdout));

        [$stdout2, $stderr2] = $this->streams();
        $runner2 = new MigrationRunner(null, $stdout2, $stderr2);

        $this->assertSame(0, $runner2->run([$this->dir], false, [], ['neon']));
        $this->assertStringContainsString('0 changed, 1 unchanged, 0 failed', $this->streamContents($stdout2));
        $this->assertSame("# Matomo\\Log\\Logger\n", file_get_contents($neon));
    }

    public function testTextFileModeSkipsExplicitFileWithExtensionOutsideSet(): void
    {
        $txt = $this->dir . '/notes.txt';
        file_put_contents($txt, "Piwik\\Foo\n");

        [$stdout, $stderr] = $this->streams();
        $exit = (new MigrationRunner(null, $stdout, $stderr))->run([$txt], false, [], ['neon']);

        $this->assertSame(0, $exit);
        $this->assertSame("Piwik\\Foo\n", file_get_contents($txt));
        $this->assertStringContainsString('0 changed, 0 unchanged, 0 failed', $this->streamContents($stdout));
    }

    public function testSubmoduleDirectoriesAreSkipped(): void
    {
        $submodule = $this->dir . '/plugins/TagManager';
        mkdir($submodule, 0777, true);
        file_put_contents($submodule . '/.git', "gitdir: ../../.git/modules/plugins/TagManager\n");
        $submoduleFile = $submodule . '/Submodule.php';
        file_put_contents($submoduleFile, "<?php\n\nnamespace Piwik;\n\nclass S {}\n");

        $changed = $this->dir . '/Changed.php';
        file_put_contents($changed, "<?php\n\nnamespace Piwik;\n\nclass A {}\n");

        [$stdout, $stderr] = $this->streams();
        $exit = (new MigrationRunner(null, $stdout, $stderr))->run([$this->dir]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('namespace Piwik;', (string) file_get_contents($submoduleFile));
        $this->assertStringNotContainsString($submoduleFile, $this->streamContents($stdout));
        $this->assertStringContainsString('1 changed, 0 unchanged, 0 failed', $this->streamContents($stdout));
    }

    /**
     * @return array{0: resource, 1: resource}
     */
    private function streams(): array
    {
        return [
            fopen('php://memory', 'w+'),
            fopen('php://memory', 'w+'),
        ];
    }

    /**
     * @param resource $stream
     */
    private function streamContents($stream): string
    {
        rewind($stream);

        return (string) stream_get_contents($stream);
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
