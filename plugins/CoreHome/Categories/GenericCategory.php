<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Categories;

use Matomo\Category\Category;

class GenericCategory extends Category
{
    protected $id = 'General_KpiMetric';
    protected $order = 1;
}
