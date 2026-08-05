<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserId\Columns;

use Matomo\Plugin\Dimension\VisitDimension;

/**
 * UserId dimension
 */
class UserId extends VisitDimension
{
    protected $nameSingular = 'UserId_UserId';
}
