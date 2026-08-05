<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\ProfileSummary;

use Matomo\Common;
use Matomo\Matomo;
use Matomo\Plugins\Live\ProfileSummary\ProfileSummaryAbstract;
use Matomo\View;

/**
 * Class EcommerceSummary
 */
class EcommerceSummary extends ProfileSummaryAbstract
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return Matomo::translate('Goals_Ecommerce');
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        if (empty($this->profile['totalEcommerceRevenue'])) {
            return '';
        }

        $view              = new View('@Ecommerce/_profileSummary.twig');
        $view->idSite      = Common::getRequestVar('idSite', null, 'int');
        $view->visitorData = $this->profile;
        return $view->render();
    }

    /**
     * @inheritdoc
     */
    public function getOrder()
    {
        return 20;
    }
}
