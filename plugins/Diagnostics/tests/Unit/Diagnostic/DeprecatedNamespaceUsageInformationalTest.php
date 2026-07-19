<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3.0 or later
 */

namespace Piwik\Plugins\Diagnostics\tests\Unit\Diagnostic;

use Piwik\Legacy\DeprecatedNamespace;
use Piwik\Plugins\Diagnostics\Diagnostic\DeprecatedNamespaceUsageInformational;
use Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult;
use Piwik\Translation\Translator;

/**
 * @group Diagnostics
 * @group DeprecatedNamespaceUsageInformationalTest
 * @group Plugins
 */
class DeprecatedNamespaceUsageInformationalTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DeprecatedNamespace::reset();
    }

    /**
     * Returns a translator mock that exposes the translation id and any
     * parameters so assertions can verify which key was used and that the
     * dynamic plugin / class data was passed through.
     */
    private function makeTranslator(): Translator
    {
        $translator = $this->createMock(Translator::class);
        $translator->method('translate')->willReturnCallback(function ($id, $args = []) {
            $args = is_array($args) ? $args : [$args];

            return empty($args) ? $id : $id . ':' . implode('|', $args);
        });

        return $translator;
    }

    public function testExecuteWithNoRecordedUsageReturnsCleanInformationalResult()
    {
        $diagnostic = new DeprecatedNamespaceUsageInformational($this->makeTranslator(), []);

        $results = $diagnostic->execute();

        $this->assertCount(1, $results);
        $this->assertSame('Diagnostics_DeprecatedNamespaceUsage', $results[0]->getLabel());
        $this->assertSame(DiagnosticResult::STATUS_INFORMATIONAL, $results[0]->getItems()[0]->getStatus());
        $this->assertStringContainsString('Diagnostics_DeprecatedNamespaceUsageNone', $results[0]->getItems()[0]->getComment());
    }

    public function testExecuteWithRecordedUsageReturnsOneResultPerPluginNamingItAndListingItsClasses()
    {
        $usage = [
            'FooBar' => [
                'Piwik\Foo' => 'Matomo\Foo',
                'Piwik\Bar' => 'Matomo\Bar',
            ],
            'Baz' => [
                'Piwik\Qux' => 'Matomo\Qux',
            ],
        ];

        $diagnostic = new DeprecatedNamespaceUsageInformational($this->makeTranslator(), $usage);

        $results = $diagnostic->execute();

        // One result per offending plugin, no clean "no usage" row.
        $this->assertCount(2, $results);

        // Results are sorted by plugin name for stable output.
        $this->assertStringContainsString('Baz', $results[0]->getLabel());
        $this->assertSame(DiagnosticResult::STATUS_INFORMATIONAL, $results[0]->getItems()[0]->getStatus());
        $comment = $results[0]->getItems()[0]->getComment();
        $this->assertStringContainsString('Piwik\Qux', $comment);
        $this->assertStringContainsString('Matomo\Qux', $comment);

        $this->assertStringContainsString('FooBar', $results[1]->getLabel());
        $this->assertSame(DiagnosticResult::STATUS_INFORMATIONAL, $results[1]->getItems()[0]->getStatus());
        $comment = $results[1]->getItems()[0]->getComment();
        $this->assertStringContainsString('Piwik\Foo', $comment);
        $this->assertStringContainsString('Matomo\Foo', $comment);
        $this->assertStringContainsString('Piwik\Bar', $comment);
        $this->assertStringContainsString('Matomo\Bar', $comment);
    }

    public function testExecuteAttributesUnattributableUsageToMatomoCore()
    {
        // Unattributed (core) usage is recorded under the empty-string key.
        $usage = [
            '' => [
                'Piwik\Something' => 'Matomo\Something',
            ],
        ];

        $diagnostic = new DeprecatedNamespaceUsageInformational($this->makeTranslator(), $usage);

        $results = $diagnostic->execute();

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Diagnostics_DeprecatedNamespaceUsageCore', $results[0]->getLabel());
        $this->assertStringContainsString('Piwik\Something', $results[0]->getItems()[0]->getComment());
    }

    public function testExecuteWithoutInjectedUsageReadsLiveFromDeprecatedNamespace()
    {
        // No usage recorded live => clean entry, proving the null-injection
        // fallback reads from DeprecatedNamespace::getRecordedUsage().
        $diagnostic = new DeprecatedNamespaceUsageInformational($this->makeTranslator());

        $results = $diagnostic->execute();

        $this->assertCount(1, $results);
        $this->assertSame('Diagnostics_DeprecatedNamespaceUsage', $results[0]->getLabel());
        $this->assertSame(DiagnosticResult::STATUS_INFORMATIONAL, $results[0]->getItems()[0]->getStatus());
    }
}
