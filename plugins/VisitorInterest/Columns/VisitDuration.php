<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\VisitorInterest\Columns;

use Matomo\Columns\Dimension;

class VisitDuration extends Dimension
{
    protected $type = self::TYPE_DURATION_S;
    protected $nameSingular = 'VisitorInterest_ColumnVisitDuration';
}
