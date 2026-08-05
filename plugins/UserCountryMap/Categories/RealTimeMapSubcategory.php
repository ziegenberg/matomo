<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserCountryMap\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;

class RealTimeMapSubcategory extends Subcategory
{
    protected $categoryId = 'General_Visitors';
    protected $id = 'UserCountryMap_RealTimeMap';
    protected $order = 9;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('UserCountryMap_RealTimeMapHelp') . '</p>';
    }
}
