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

class ChallengeViewVisitsLog extends Challenge
{
    public function getName()
    {
        return Matomo::translate('Tour_ViewX', Matomo::translate('Live_VisitsLog'));
    }

    public function getDescription()
    {
        return Matomo::translate('Tour_ViewVisitsLogDescription');
    }

    public function getId()
    {
        return 'view_visits_log';
    }

    public function getUrl()
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/faq/reports/the-visits-log-report/');
    }
}
