<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Tour\Engagement;

use Matomo\Matomo;
use Matomo\Url;

class ChallengeSelectDateRange extends Challenge
{
    public function getName()
    {
        return Matomo::translate('Tour_SelectDateRange');
    }

    public function getDescription()
    {
        return Matomo::translate('Tour_SelectDateRangeDescription');
    }

    public function getId()
    {
        return 'select_date_range';
    }

    public function getUrl()
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/faq/reports/data-selectors-in-matomo/');
    }
}
