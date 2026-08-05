<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Insights\Widgets;

use Matomo\Widget\WidgetConfig;

class GetOverallMoversAndShakers extends \Matomo\Widget\Widget
{
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('Insights_WidgetCategory');
        $config->setName('Insights_MoversAndShakersWidgetTitle');
    }
}
