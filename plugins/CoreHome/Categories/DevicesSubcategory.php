<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;

class DevicesSubcategory extends Subcategory
{
    protected $categoryId = 'General_Visitors';
    protected $id = 'DevicesDetection_Devices';
    protected $order = 15;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('CoreHome_DevicesSubcategoryHelp') . '</p>';
    }
}
