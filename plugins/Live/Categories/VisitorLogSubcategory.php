<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Live\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;
use Matomo\Url;

class VisitorLogSubcategory extends Subcategory
{
    protected $categoryId = 'General_Visitors';
    protected $id = 'Live_VisitorLog';
    protected $order = 5;

    public function getHelp()
    {
        $help = '<p>' . Matomo::translate('Live_VisitorLogSubcategoryHelp1') . '</p>';
        $help .= '<p>' . Matomo::translate('Live_VisitorLogSubcategoryHelp2') . '</p>';
        $help .= '<p>' . Url::getExternalLinkTag('https://matomo.org/docs/real-time/', null, null, 'App.Live.getLastVisitsDetails')
            . Matomo::translate('Live_VisitorLogSubcategoryHelp3') . '</a></p>';
        return $help;
    }
}
