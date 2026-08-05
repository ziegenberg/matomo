<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Events\DataTable\Filter;

use Matomo\DataTable\BaseFilter;
use Matomo\DataTable;
use Matomo\Matomo;
use Matomo\Plugins\Events\Archiver;

class ReplaceEventNameNotSet extends BaseFilter
{
    /**
     * @param DataTable $table The table to eventually filter.
     */
    public function __construct($table)
    {
        parent::__construct($table);
    }

    /**
     * @param DataTable $table
     */
    public function filter($table)
    {
        $row = $table->getRowFromLabel(Archiver::EVENT_NAME_NOT_SET);
        if ($row) {
            $row->setColumn('label', Matomo::translate('General_NotDefined', Matomo::translate('Events_EventName')));
        }
    }
}
