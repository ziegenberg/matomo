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

class EngagementSubcategory extends Subcategory
{
    protected $categoryId = 'General_Actions';
    protected $id = 'VisitorInterest_Engagement';
    protected $order = 46;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('CoreHome_EngagementSubcategoryHelp1') . '</p>'
            . '<p>' . Matomo::translate('CoreHome_EngagementSubcategoryHelp2') . '</p>';
    }
}
