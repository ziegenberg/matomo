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

class RealTimeVisitorsSubcategory extends Subcategory
{
    protected $categoryId = 'General_Visitors';
    protected $id = 'General_RealTime';
    protected $order = 7;

    public function getHelp()
    {
        $result = '<p>' . Matomo::translate('Live_RealTimeHelp1') . '</p>';
        $result .= '<p>' . Matomo::translate('Live_RealTimeHelp2') . '</p>';
        return $result;
    }
}
