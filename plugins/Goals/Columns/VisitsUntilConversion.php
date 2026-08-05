<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals\Columns;

use Matomo\Columns\Dimension;

class VisitsUntilConversion extends Dimension
{
    protected $type = self::TYPE_NUMBER;
    protected $nameSingular = 'Goals_VisitsUntilConv';
}
