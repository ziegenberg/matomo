<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\ArchivingMetrics;

use Matomo\Period;
use Matomo\Segment;
use Matomo\Site;

class ArchivingMetrics extends \Matomo\Plugin
{
    public function registerEvents()
    {
        return [
            'CoreAdminHome.archiveReports.start' => 'onArchiveReportsStart',
            'CoreAdminHome.archiveReports.complete' => 'onArchiveReportsComplete',
        ];
    }

    public function onArchiveReportsStart(
        int $idSite,
        Period $period,
        Segment $segment,
        string $plugin,
        $report,
        bool $isArchivePhpTriggered
    ): void {
        $timer = Timer::getInstance($isArchivePhpTriggered);
        $context = $this->buildContext($idSite, $period, $segment, $plugin, $report);

        $timer->start($context);
    }

    /**
     * @param int[] $idArchives
     */
    public function onArchiveReportsComplete(
        int $idSite,
        Period $period,
        Segment $segment,
        string $plugin,
        $report,
        bool $isArchivePhpTriggered,
        array $idArchives,
        bool $wasCached
    ): void {
        $timer = Timer::getInstance($isArchivePhpTriggered);
        $context = $this->buildContext($idSite, $period, $segment, $plugin, $report);

        $timer->complete($context, $idArchives, $wasCached);
    }

    private function buildContext(int $idSite, Period $period, Segment $segment, string $plugin, $report): Context
    {
        $site = new Site($idSite);
        return new Context($idSite, $period, $segment, $plugin, $report, $site->getTimezone());
    }
}
