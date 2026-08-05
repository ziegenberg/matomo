<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Tour\Engagement;

use Matomo\Matomo;
use Matomo\Plugins\Tour\Dao\DataFinder;
use Matomo\Url;

class ChallengeCustomiseDashboard extends Challenge
{
    private DataFinder $finder;

    /**
     * @var null|bool
     */
    private $completed = null;

    public function __construct(DataFinder $dataFinder)
    {
        $this->finder = $dataFinder;
    }

    public function getName()
    {
        return Matomo::translate('Tour_CustomiseDashboard');
    }

    public function getDescription()
    {
        return Matomo::translate('Tour_CustomiseDashboardDescription');
    }

    public function getId()
    {
        return 'customise_dashboard';
    }

    public function isCompleted(string $login)
    {
        if (!isset($this->completed)) {
            $this->completed = $this->finder->hasAddedOrCustomisedDashboard($login);
        }
        return $this->completed;
    }

    public function getUrl()
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/faq/dashboards/create-dashboards-and-customise-widgets-and-layout/');
    }
}
