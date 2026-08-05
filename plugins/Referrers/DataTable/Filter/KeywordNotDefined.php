<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Referrers\DataTable\Filter;

use Matomo\DataTable;

class KeywordNotDefined extends DataTable\Filter\ColumnCallbackReplace
{
    /**
     * @param DataTable $table The table to eventually filter.
     */
    public function __construct($table)
    {
        parent::__construct($table, 'label', 'Matomo\Plugins\Referrers\API::getCleanKeyword');
    }
}
