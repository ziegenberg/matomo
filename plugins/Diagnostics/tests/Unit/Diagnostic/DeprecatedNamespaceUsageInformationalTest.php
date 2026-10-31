<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Diagnostics\tests\Unit\Diagnostic;

use Piwik\Filesystem;
use Piwik\Plugins\Diagnostics\Diagnostic\DeprecatedNamespaceUsageInformational;
use Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult;

class DeprecatedNamespaceUsageInformationalTest extends \PHPUnit\Framework\TestCase
{
    private string $tempRoot = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempRoot = sys_get_temp_dir() . '/piwik-deprecated-ns-' . uniqid();
        mkdir($this->tempRoot, 0755, true);
    }

    protected function tearDown(): void
    {
        Filesystem::unlinkRecursive($this->tempRoot, true);
        $this->tempRoot = '';

        parent::tearDown();
    }

    /**
     * @param array<string, string> $files Map of relative path to file contents.
     * @return list<string> Absolute paths of the created files.
     */
    private function createPluginFiles(string $pluginName, array $files): array
    {
        $pluginDirectory = $this->tempRoot . '/' . $pluginName;
        $paths = [];

        foreach ($files as $relativePath => $contents) {
            $absolutePath = $pluginDirectory . '/' . $relativePath;
            $directory = dirname($absolutePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($absolutePath, $contents);
            $paths[] = $absolutePath;
        }

        return $paths;
    }

    public function testNoUsageYieldsSingleCleanInformationalRow()
    {
        $files = $this->createPluginFiles('CleanPlugin', [
            'Plugin.php' => '<?php namespace Matomo\Plugins\CleanPlugin;',
            'api.php' => '<?php class Api {}',
        ]);

        $diagnostic = new DeprecatedNamespaceUsageInformational(['CleanPlugin' => $files]);
        $results = $diagnostic->execute();

        $this->assertCount(1, $results);
        $this->assertInstanceOf(DiagnosticResult::class, $results[0]);
        $this->assertEquals(DiagnosticResult::STATUS_INFORMATIONAL, $results[0]->getItems()[0]->getStatus());
        $this->assertEquals('Deprecated Piwik namespace usage', $results[0]->getLabel());
        $this->assertStringContainsString('No deprecated Piwik\ namespace usage found', $results[0]->getItems()[0]->getComment());
    }

    public function testPerPluginUsageYieldsOneResultPerPluginListingUpToFiveFiles()
    {
        $filesA = $this->createPluginFiles('PluginA', [
            'PluginA.php' => '<?php use Piwik\Foo;',
            'api.php' => '<?php use Piwik\Bar;',
            'clean.php' => '<?php namespace Matomo\Plugins\PluginA;',
            'tests/OffenderTest.php' => '<?php use Piwik\Baz;',
            'lang/legacy.php' => '<?php use Piwik\Legacy;',
        ]);
        $filesB = $this->createPluginFiles('PluginB', [
            'File1.php' => '<?php use Piwik\One;',
            'File2.php' => '<?php use Piwik\Two;',
            'File3.php' => '<?php use Piwik\Three;',
            'File4.php' => '<?php use Piwik\Four;',
            'File5.php' => '<?php use Piwik\Five;',
        ]);

        $diagnostic = new DeprecatedNamespaceUsageInformational([
            'PluginA' => $filesA,
            'PluginB' => $filesB,
        ]);
        $results = $diagnostic->execute();

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(DiagnosticResult::class, $result);
            $this->assertEquals(DiagnosticResult::STATUS_INFORMATIONAL, $result->getItems()[0]->getStatus());
        }

        $this->assertEquals('Deprecated Piwik namespace usage in PluginA', $results[0]->getLabel());
        $this->assertEquals('Deprecated Piwik namespace usage in PluginB', $results[1]->getLabel());

        $commentA = $results[0]->getItems()[0]->getComment();
        $this->assertStringContainsString('PluginA.php', $commentA);
        $this->assertStringContainsString('api.php', $commentA);
        $this->assertStringNotContainsString('clean.php', $commentA);
        $this->assertStringNotContainsString('OffenderTest.php', $commentA);
        $this->assertStringNotContainsString('legacy.php', $commentA);

        $commentB = $results[1]->getItems()[0]->getComment();
        foreach (['File1.php', 'File2.php', 'File3.php', 'File4.php', 'File5.php'] as $basename) {
            $this->assertStringContainsString($basename, $commentB);
        }
        $this->assertStringNotContainsString('(and', $commentB);
    }

    public function testFilesBeyondLimitAreSummarisedWithRemainingCount()
    {
        $files = [];
        for ($i = 1; $i <= 7; $i++) {
            $files['File' . $i . '.php'] = '<?php use Piwik\Class' . $i . ';';
        }
        $paths = $this->createPluginFiles('PluginC', $files);

        $diagnostic = new DeprecatedNamespaceUsageInformational(['PluginC' => $paths]);
        $results = $diagnostic->execute();

        $this->assertCount(1, $results);

        $comment = $results[0]->getItems()[0]->getComment();
        foreach (['File1.php', 'File2.php', 'File3.php', 'File4.php', 'File5.php'] as $basename) {
            $this->assertStringContainsString($basename, $comment);
        }
        $this->assertStringNotContainsString('File6.php', $comment);
        $this->assertStringNotContainsString('File7.php', $comment);
        $this->assertStringContainsString('(and 2 more)', $comment);
    }

    public function testLiveScanFallbackReturnsWellFormedResults()
    {
        $diagnostic = new DeprecatedNamespaceUsageInformational();
        $results = $diagnostic->execute();

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(DiagnosticResult::class, $result);
            $this->assertEquals(DiagnosticResult::STATUS_INFORMATIONAL, $result->getItems()[0]->getStatus());
            $this->assertNotEquals('', $result->getLabel());
            $this->assertStringStartsWith('Deprecated Piwik namespace usage', $result->getLabel());
        }
    }
}
