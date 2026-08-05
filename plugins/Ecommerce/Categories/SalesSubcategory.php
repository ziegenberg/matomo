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

class SalesSubcategory extends Subcategory
{
    protected $categoryId = 'Goals_Ecommerce';
    protected $id = 'Ecommerce_Sales';
    protected $order = 15;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Ecommerce_SalesSubcategoryHelp1') . '</p>'
            . '<p>' . Matomo::translate('Ecommerce_SalesSubcategoryHelp2') . '</p>';
    }
}
