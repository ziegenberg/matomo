<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Referrers\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;

class ReferrersOverviewSubcategory extends Subcategory
{
    protected $categoryId = 'Referrers_Referrers';
    protected $id = 'General_Overview';
    protected $order = 2;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Referrers_ReferrersOverviewSubcategoryHelp1') . '</p>'
            . '<p>' . Matomo::translate('Referrers_ReferrersOverviewSubcategoryHelp2') . '</p>';
    }
}
