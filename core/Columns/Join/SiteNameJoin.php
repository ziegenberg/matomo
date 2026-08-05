<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Columns\Join;

use Matomo\Columns;

/**
 * @api
 * @since 3.1.0
 */
class SiteNameJoin extends Columns\Join
{
    public function __construct()
    {
        parent::__construct('site', 'idsite', 'name');
    }
}
