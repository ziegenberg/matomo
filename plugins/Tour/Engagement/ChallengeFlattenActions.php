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

class ChallengeFlattenActions extends Challenge
{
    public function getName()
    {
        return Matomo::translate('Tour_FlattenActions');
    }

    public function getDescription()
    {
        return Matomo::translate('Tour_FlattenActionsDescription');
    }

    public function getId()
    {
        return 'flatten_actions';
    }

    public function getUrl()
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/faq/reports/graphs-and-visualisations-in-matomo/#flattening-reports');
    }
}
