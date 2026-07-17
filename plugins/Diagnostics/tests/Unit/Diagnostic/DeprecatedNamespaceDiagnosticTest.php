<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Diagnostics\tests\Unit\Diagnostic;

use Piwik\Plugins\Diagnostics\Diagnostic\DeprecatedNamespaceDiagnostic;
use Piwik\Plugins\Diagnostics\Diagnostic\DeprecatedNamespaceUsageProvider;
use Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult;
use Piwik\Plugins\Diagnostics\DiagnosticService;

/**
 * @group Diagnostics
 * @group DeprecatedNamespaceDiagnostic
 */
class DeprecatedNamespaceDiagnosticTest extends \PHPUnit\Framework\TestCase
{
    public function testNoRecordedUsageShowsSingleCleanEntry()
    {
        $diagnostic = new DeprecatedNamespaceDiagnostic(new FakeUsageProvider([]));

        $results = $diagnostic->execute();

        $this->assertCount(1, $results);
        $items = $results[0]->getItems();
        $this->assertCount(1, $items);
        $this->assertSame(DiagnosticResult::STATUS_INFORMATIONAL, $items[0]->getStatus());
        $this->assertSame('No deprecated Piwik\ namespace usage observed.', $items[0]->getComment());
    }

    public function testListsEachOffendingPluginWithItsDeprecatedClasses()
    {
        $diagnostic = new DeprecatedNamespaceDiagnostic(new FakeUsageProvider([
            ['piwik' => 'Piwik\A', 'matomo' => 'Matomo\A', 'plugin' => 'Foo'],
            ['piwik' => 'Piwik\B', 'matomo' => 'Matomo\B', 'plugin' => 'Bar'],
        ]));

        $results = $diagnostic->execute();

        $this->assertCount(1, $results);
        $this->assertSame(DeprecatedNamespaceDiagnostic::LABEL, $results[0]->getLabel());
        $items = $results[0]->getItems();
        $this->assertCount(2, $items);

        // Plugins sorted alphabetically.
        $this->assertSame('Bar uses deprecated Piwik\ namespace: Piwik\B', $items[0]->getComment());
        $this->assertSame('Foo uses deprecated Piwik\ namespace: Piwik\A', $items[1]->getComment());

        foreach ($items as $item) {
            $this->assertSame(DiagnosticResult::STATUS_INFORMATIONAL, $item->getStatus());
        }
    }

    public function testMultipleClassesPerPluginAreListedAndDeduped()
    {
        $diagnostic = new DeprecatedNamespaceDiagnostic(new FakeUsageProvider([
            ['piwik' => 'Piwik\Common', 'matomo' => 'Matomo\Common', 'plugin' => 'Foo'],
            ['piwik' => 'Piwik\Settings', 'matomo' => 'Matomo\Settings', 'plugin' => 'Foo'],
            // Duplicate class for the same plugin: defensively collapsed.
            ['piwik' => 'Piwik\Common', 'matomo' => 'Matomo\Common', 'plugin' => 'Foo'],
        ]));

        $items = $diagnostic->execute()[0]->getItems();

        $this->assertCount(1, $items);
        $this->assertSame(
            'Foo uses deprecated Piwik\ namespace: Piwik\Common, Piwik\Settings',
            $items[0]->getComment()
        );
    }

    public function testCoreOriginatedUsageIsSkipped()
    {
        // A deprecation attributed to core (plugin === null) is not a plugin: the diagnostic
        // surfaces per-plugin usage only. Core deprecations are a separate concern (the
        // source-clean guard), so they do not appear here.
        $diagnostic = new DeprecatedNamespaceDiagnostic(new FakeUsageProvider([
            ['piwik' => 'Piwik\A', 'matomo' => 'Matomo\A', 'plugin' => null],
        ]));

        $items = $diagnostic->execute()[0]->getItems();

        $this->assertCount(1, $items);
        $this->assertSame('No deprecated Piwik\ namespace usage observed.', $items[0]->getComment());
    }

    public function testPluginsAndClassesAreSortedForDeterministicOutput()
    {
        $diagnostic = new DeprecatedNamespaceDiagnostic(new FakeUsageProvider([
            ['piwik' => 'Piwik\Zeta', 'matomo' => 'Matomo\Zeta', 'plugin' => 'ZetaPlugin'],
            ['piwik' => 'Piwik\Alpha', 'matomo' => 'Matomo\Alpha', 'plugin' => 'AlphaPlugin'],
            ['piwik' => 'Piwik\Mid', 'matomo' => 'Matomo\Mid', 'plugin' => 'AlphaPlugin'],
        ]));

        $items = $diagnostic->execute()[0]->getItems();

        $this->assertSame(
            'AlphaPlugin uses deprecated Piwik\ namespace: Piwik\Alpha, Piwik\Mid',
            $items[0]->getComment()
        );
        $this->assertSame(
            'ZetaPlugin uses deprecated Piwik\ namespace: Piwik\Zeta',
            $items[1]->getComment()
        );
    }

    public function testRunsThroughDiagnosticServiceAsInformational()
    {
        // The diagnostic plugs into the existing DiagnosticService mock pattern: it is
        // registered as an informational diagnostic and its results flow through the report.
        $diagnostic = new DeprecatedNamespaceDiagnostic(new FakeUsageProvider([
            ['piwik' => 'Piwik\A', 'matomo' => 'Matomo\A', 'plugin' => 'Foo'],
        ]));

        $service = new DiagnosticService([], [], [$diagnostic], []);
        $results = $service->runDiagnostics()->getAllResults();

        $this->assertCount(1, $results);
        $this->assertSame(DeprecatedNamespaceDiagnostic::LABEL, $results[0]->getLabel());
        $items = $results[0]->getItems();
        $this->assertCount(1, $items);
        $this->assertSame(DiagnosticResult::STATUS_INFORMATIONAL, $items[0]->getStatus());
    }
}

/**
 * In-memory provider for unit-testing the diagnostic in isolation from the global
 * LegacyAutoloader state.
 */
class FakeUsageProvider implements DeprecatedNamespaceUsageProvider
{
    /** @var array<int, array{piwik: string, matomo: string, plugin: string|null}> */
    private $usages;

    /**
     * @param array<int, array{piwik: string, matomo: string, plugin: string|null}> $usages
     */
    public function __construct(array $usages)
    {
        $this->usages = $usages;
    }

    public function getRecordedUsages(): array
    {
        return $this->usages;
    }
}
