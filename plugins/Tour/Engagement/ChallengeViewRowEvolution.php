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

class ChallengeViewRowEvolution extends Challenge
{
    public function getName()
    {
        return Matomo::translate('Tour_ViewX', Matomo::translate('Tour_RowEvolution'));
    }

    public function getDescription()
    {
        return Matomo::translate('Tour_ViewRowEvolutionDescription');
    }

    public function getId()
    {
        return 'view_row_evolution';
    }

    public function getUrl()
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/docs/row-evolution/');
    }
}
