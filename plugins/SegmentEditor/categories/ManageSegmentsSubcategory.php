<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\SegmentEditor\Categories;

use Matomo\Category\Subcategory;

class ManageSegmentsSubcategory extends Subcategory
{
    protected $categoryId = 'General_Visitors';
    protected $id = 'CoreHome_Segments';
    protected $order = 9999;
}
