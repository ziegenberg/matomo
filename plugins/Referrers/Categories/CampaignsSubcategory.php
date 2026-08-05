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

class CampaignsSubcategory extends Subcategory
{
    protected $categoryId = 'Referrers_Referrers';
    protected $id = 'Referrers_Campaigns';
    protected $order = 20;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Referrers_CampaignsSubcategoryHelp') . '</p>';
    }
}
