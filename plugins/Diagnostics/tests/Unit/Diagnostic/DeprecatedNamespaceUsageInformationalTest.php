<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3.0 or later
 */

namespace Matomo\Plugins\Diagnostics\tests\Unit\Diagnostic;

/**
 * @group Diagnostics
 * @group DeprecatedNamespaceUsageInformationalTest
 * @group Plugins
 */
class DeprecatedNamespaceUsageInformationalTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Returns a translator mock that exposes the translation id and any
     * parameters so assertions can verify which key was used and that the
     * dynamic plugin name was passed through.
     */
    private function makeTranslator(): \Matomo\Translation\Translator
    {
        $translator = $this->createMock(\Matomo\Translation\Translator::class);
        $translator->method('translate')->willReturnCallback(function ($id, $args = []) {
            $args = is_array($args) ? $args : [$args];

            return empty($args) ? $id : $id . ':' . implode('|', $args);
        });

        return $translator;
    }

    public function testExecuteWithNoInjectedUsageReturnsCleanInformationalResult()
    {
        $diagnostic = new \Matomo\Plugins\Diagnostics\Diagnostic\DeprecatedNamespaceUsageInformational(
            $this->makeTranslator(),
            []
        );

        $results = $diagnostic->execute();

        $this->assertCount(1, $results);
        $this->assertSame('Diagnostics_DeprecatedNamespaceUsage', $results[0]->getLabel());
        $this->assertSame(\Matomo\Plugins\Diagnostics\Diagnostic\DiagnosticResult::STATUS_INFORMATIONAL, $results[0]->getItems()[0]->getStatus());
        $this->assertStringContainsString('Diagnostics_DeprecatedNamespaceUsageNone', $results[0]->getItems()[0]->getComment());
    }

    public function testExecuteWithUsageReturnsOneResultPerPluginNamingItAndListingItsFiles()
    {
        $usage = [
            'FooBar' => ['Controller.php', 'API.php'],
            'Baz' => ['Service.php'],
        ];

        $diagnostic = new \Matomo\Plugins\Diagnostics\Diagnostic\DeprecatedNamespaceUsageInformational(
            $this->makeTranslator(),
            $usage
        );

        $results = $diagnostic->execute();

        // One result per offending plugin, no clean "no usage" row.
        $this->assertCount(2, $results);

        // Results are sorted by plugin name for stable output.
        $this->assertStringContainsString('Baz', $results[0]->getLabel());
        $this->assertSame(\Matomo\Plugins\Diagnostics\Diagnostic\DiagnosticResult::STATUS_INFORMATIONAL, $results[0]->getItems()[0]->getStatus());
        $comment = $results[0]->getItems()[0]->getComment();
        $this->assertStringContainsString('Service.php', $comment);
        $this->assertStringContainsString('1 file', $comment);

        $this->assertStringContainsString('FooBar', $results[1]->getLabel());
        $this->assertSame(\Matomo\Plugins\Diagnostics\Diagnostic\DiagnosticResult::STATUS_INFORMATIONAL, $results[1]->getItems()[0]->getStatus());
        $comment = $results[1]->getItems()[0]->getComment();
        $this->assertStringContainsString('Controller.php', $comment);
        $this->assertStringContainsString('API.php', $comment);
        $this->assertStringContainsString('2 files', $comment);
    }

    public function testExecuteSummarisesFilesBeyondTheListLimit()
    {
        $files = [];
        for ($i = 1; $i <= 7; $i++) {
            $files[] = "File$i.php";
        }

        $usage = ['Big' => $files];

        $diagnostic = new \Matomo\Plugins\Diagnostics\Diagnostic\DeprecatedNamespaceUsageInformational(
            $this->makeTranslator(),
            $usage
        );

        $results = $diagnostic->execute();

        $this->assertCount(1, $results);
        $comment = $results[0]->getItems()[0]->getComment();
        // Exactly the first five are listed by name.
        $this->assertStringContainsString('File1.php', $comment);
        $this->assertStringContainsString('File5.php', $comment);
        $this->assertStringNotContainsString('File6.php', $comment);
        // The rest are summarised as a count.
        $this->assertStringContainsString('7 files', $comment);
        $this->assertStringContainsString('2 more', $comment);
    }

    public function testExecuteWithoutInjectedUsageReadsLiveFromScan()
    {
        // No usage injected: the diagnostic falls back to a live scan of installed
        // plugins. Whatever it returns, executing must not throw and must return
        // an array of DiagnosticResult (either the clean row or per-plugin rows).
        $diagnostic = new \Matomo\Plugins\Diagnostics\Diagnostic\DeprecatedNamespaceUsageInformational(
            $this->makeTranslator()
        );

        $results = $diagnostic->execute();

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(\Matomo\Plugins\Diagnostics\Diagnostic\DiagnosticResult::class, $result);
        }
    }
}
