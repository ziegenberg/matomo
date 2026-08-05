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

class EcommerceLogSubcategory extends Subcategory
{
    protected $categoryId = 'Goals_Ecommerce';
    protected $id = 'Goals_EcommerceLog';
    protected $order = 5;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Ecommerce_EcommerceLogSubcategoryHelp1') . '</p>'
            . '<p>' . Matomo::translate('Ecommerce_EcommerceLogSubcategoryHelp2') . '</p>';
    }
}
