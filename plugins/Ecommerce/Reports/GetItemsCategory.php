<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Reports;

use Matomo\Matomo;
use Matomo\Plugins\Ecommerce\Columns\ProductCategory;

class GetItemsCategory extends BaseItem
{
    protected function init()
    {
        parent::init();

        $this->name      = Matomo::translate('Goals_ProductCategory');
        $this->dimension = new ProductCategory();
        $this->order     = 32;

        $this->subcategoryId = 'Goals_Products';
    }
}
