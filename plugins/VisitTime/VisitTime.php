<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\VisitTime;

use Matomo\Plugins\SegmentEditor\Settings\LimitSegments;
use Matomo\Segment\SegmentsList;

// empty plugin definition, otherwise plugin won't be installed during test run
class VisitTime extends \Matomo\Plugin
{
    public function registerEvents()
    {
        return [
            'Segment.filterSegments' => 'filterSegments',
        ];
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
            $list->remove('General_Visitors', 'visitLocalHour');
            $list->remove('General_Visitors', 'visitLocalMinute');
        }
    }
}
