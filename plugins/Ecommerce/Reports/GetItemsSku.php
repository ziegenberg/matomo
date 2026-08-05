<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Reports;

use Matomo\Matomo;
use Matomo\Plugins\Ecommerce\Columns\ProductSku;

class GetItemsSku extends BaseItem
{
    protected function init()
    {
        parent::init();

        $this->name      = Matomo::translate('Goals_ProductSKU');
        $this->dimension = new ProductSku();
        $this->order     = 31;

        $this->subcategoryId = 'Goals_Products';
    }
}
