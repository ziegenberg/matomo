<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Categories;

use Matomo\Category\Category;

class EcommerceCategory extends Category
{
    protected $id = 'Goals_Ecommerce';
    protected $order = 20;
    protected $icon = 'icon-reporting-ecommerce';
}
