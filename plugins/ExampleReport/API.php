<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\ExampleReport;

use Matomo\DataTable;
use Matomo\DataTable\Row;

/**
 * API for plugin ExampleReport
 *
 * @method static \Matomo\Plugins\ExampleReport\API getInstance()
 */
class API extends \Matomo\Plugin\API
{
    /**
     * Another example method that returns a data table.
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getExampleReport($idSite, $period, $date, $segment = false)
    {
        $table = new DataTable();

        $table->addRowFromArray(array(Row::COLUMNS => array('nb_visits' => 5)));

        return $table;
    }
}
