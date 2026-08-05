<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals\Categories;

use Matomo\Category\Category;

class GoalsCategory extends Category
{
    protected $id = 'Goals_Goals';
    protected $order = 25;
    protected $icon = 'icon-reporting-goal';
}
