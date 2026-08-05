<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals\Columns;

use Matomo\Plugin\Dimension\ConversionDimension;

class Revenue extends ConversionDimension
{
    protected $columnName = 'revenue';
    protected $type = self::TYPE_MONEY;
    protected $category = 'Goals_Goals';
    protected $nameSingular = 'Goals_ColumnOverallRevenue';
}
