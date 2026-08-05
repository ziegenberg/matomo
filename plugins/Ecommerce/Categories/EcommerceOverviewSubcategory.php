<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;
use Matomo\Url;

class EcommerceOverviewSubcategory extends Subcategory
{
    protected $categoryId = 'Goals_Ecommerce';
    protected $id = 'General_Overview';
    protected $order = 2;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Ecommerce_EcommerceOverviewSubcategoryHelp1') . '</p>'
            . '<p>' . Matomo::translate('Ecommerce_EcommerceOverviewSubcategoryHelp2') . '</p>'
            . '<p>' . Url::getExternalLinkTag('https://matomo.org/docs/ecommerce-analytics/', null, null, 'App.Ecommerce.Overview')
            . Matomo::translate('Ecommerce_EcommerceOverviewSubcategoryHelp3') . '</a></p>';
    }
}
