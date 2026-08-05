<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\ExampleReport\Reports;

use Matomo\Plugin\Report;

abstract class Base extends Report
{
    protected function init()
    {
        $this->categoryId = 'ExampleCategory';
    }
}
