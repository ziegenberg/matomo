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

class ChallengeChangeVisualisation extends Challenge
{
    public function getName()
    {
        return Matomo::translate('Tour_ChangeVisualisation');
    }

    public function getDescription()
    {
        return Matomo::translate('Tour_ChangeVisualisationDescription');
    }

    public function getId()
    {
        return 'change_visualisations';
    }

    public function getUrl()
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/faq/reports/graphs-and-visualisations-in-matomo/');
    }
}
