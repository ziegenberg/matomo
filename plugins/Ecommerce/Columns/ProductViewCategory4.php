<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Columns;

use Matomo\Columns\DimensionSegmentFactory;
use Matomo\Segment\SegmentsList;

class ProductViewCategory4 extends ProductViewCategory
{
    protected $columnName = 'idaction_product_cat4';
    protected $categoryNumber = 4;

    public function configureSegments(SegmentsList $segmentsList, DimensionSegmentFactory $dimensionSegmentFactory)
    {
        // handled in category 1
    }
}
