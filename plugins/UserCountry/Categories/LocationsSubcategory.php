<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserCountry\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;

class LocationsSubcategory extends Subcategory
{
    protected $categoryId = 'General_Visitors';
    protected $id = 'UserCountry_SubmenuLocations';
    protected $order = 10;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('UserCountry_LocationsSubcategoryHelp') . '</p>';
    }
}
