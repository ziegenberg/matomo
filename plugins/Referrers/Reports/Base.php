<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Referrers\Reports;

abstract class Base extends \Matomo\Plugin\Report
{
    protected function init()
    {
        $this->categoryId = 'Referrers_Referrers';
    }
}
