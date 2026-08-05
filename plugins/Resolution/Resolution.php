<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Resolution;

use Matomo\Plugins\Resolution\Settings\ScreenResolutionDetectionDisabled;
use Matomo\Plugins\SegmentEditor\Settings\LimitSegments;
use Matomo\Segment\SegmentsList;
use Matomo\Tracker\Cache as TrackerCache;

class Resolution extends \Matomo\Plugin
{
    public function registerEvents()
    {
        return [
            'Segment.filterSegments' => 'filterSegments',
        ];
    }

    /**
     * Check if compliance policy disables screen resolution detection
     *
     * @throws \Matomo\Exception\DI\DependencyException
     * @throws \Matomo\Exception\DI\NotFoundException
     */
    public static function isScreenResolutionDetectionDisabledByCompliancePolicy(?int $idSite = null): bool
    {
        $cache = TrackerCache::getCacheWebsiteAttributes($idSite);
        $cacheKey = ScreenResolutionDetectionDisabled::class;
        return (($cache[$cacheKey] ?? false) === true);
    }

    public function filterSegments(SegmentsList &$list, array $idSites)
    {
        $limitSegmentsSettingEnabled = false;
        if (empty($idSites)) {
            $limitSegmentsSettingEnabled = LimitSegments::getInstance()->getValue();
        } else {
            foreach ($idSites as $idsite) {
                $limitSegmentsSettingEnabled |= LimitSegments::getInstance($idsite)->getValue();
            }
        }
        if ($limitSegmentsSettingEnabled) {
            $list->remove('General_Visitors', 'resolution');
        }
    }
}
