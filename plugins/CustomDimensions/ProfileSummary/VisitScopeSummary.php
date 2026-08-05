<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CustomDimensions\ProfileSummary;

use Matomo\Matomo;
use Matomo\Plugins\CustomDimensions\CustomDimensions;
use Matomo\Plugins\Live\ProfileSummary\ProfileSummaryAbstract;
use Matomo\View;

/**
 * Class VisitScopeSummary
 */
class VisitScopeSummary extends ProfileSummaryAbstract
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return Matomo::translate('CustomDimensions_CustomDimensions') . ' ' . Matomo::translate('General_TrackingScopeVisit');
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        if (empty($this->profile['customDimensions']) || empty($this->profile['customDimensions'][CustomDimensions::SCOPE_VISIT])) {
            return '';
        }

        $view              = new View('@CustomDimensions/_profileSummary.twig');
        $view->visitorData = $this->profile;
        $view->scopeName   = Matomo::translate('General_TrackingScopeVisit');
        $view->dimensions  = $this->profile['customDimensions'][CustomDimensions::SCOPE_VISIT];

        return $view->render();
    }

    /**
     * @inheritdoc
     */
    public function getOrder()
    {
        return 10;
    }
}
