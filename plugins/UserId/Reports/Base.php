<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserId\Reports;

use Matomo\Plugin\Report;
use Matomo\Url;

abstract class Base extends Report
{
    protected function init()
    {
        $this->categoryId = 'General_Visitors';
        $this->onlineGuideUrl = Url::addCampaignParametersToMatomoLink('https://matomo.org/docs/user-id/');
    }
}
