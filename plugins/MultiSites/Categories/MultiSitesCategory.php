<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\MultiSites\Categories;

use Matomo\Category\Category;

class MultiSitesCategory extends Category
{
    protected $id = 'General_MultiSitesSummary';
    protected $order = 3;
}
