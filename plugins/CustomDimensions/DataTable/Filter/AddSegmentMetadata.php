<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CustomDimensions\DataTable\Filter;

use Matomo\DataTable\BaseFilter;
use Matomo\DataTable;
use Matomo\Plugins\CustomDimensions\Archiver;
use Matomo\Plugins\CustomDimensions\Tracker\CustomDimensionsRequestProcessor;

class AddSegmentMetadata extends BaseFilter
{
    private $idDimension;

    /**
     * @param DataTable $table The table to eventually filter.
     */
    public function __construct($table, $idDimension)
    {
        parent::__construct($table);
        $this->idDimension = $idDimension;
    }

    /**
     * @param DataTable $table
     */
    public function filter($table)
    {
        $dimension = CustomDimensionsRequestProcessor::buildCustomDimensionTrackingApiName($this->idDimension);

        foreach ($table->getRows() as $row) {
            $label = $row->getColumn('label');
            if ($label !== false) {
                if ($label === Archiver::LABEL_CUSTOM_VALUE_NOT_DEFINED) {
                    $label = '';
                }
                $row->setMetadata('segment', $dimension . '==' . urlencode($label));
            }

            $subTable = $row->getSubtable();
            if ($subTable) {
                $subTable->filter('Matomo\Plugins\CustomDimensions\DataTable\Filter\AddSubtableSegmentMetadata', array($this->idDimension, $label));
            }
        }
    }
}
