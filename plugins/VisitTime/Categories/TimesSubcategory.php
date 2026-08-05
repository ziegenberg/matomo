<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\VisitTime\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;

class TimesSubcategory extends Subcategory
{
    protected $categoryId = 'General_Visitors';
    protected $id = 'VisitTime_SubmenuTimes';
    protected $order = 35;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('VisitTime_TimesSubcategoryHelp') . '</p>';
    }
}
