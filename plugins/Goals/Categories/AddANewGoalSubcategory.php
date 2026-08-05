<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals\Categories;

use Matomo\Category\Subcategory;

class AddANewGoalSubcategory extends Subcategory
{
    protected $categoryId = 'Goals_Goals';
    protected $id = 'Goals_AddNewGoal';
    protected $order = 1;
}
