<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CustomDimensions\tests\Unit\DataTable\Filter;

use Matomo\DataTable;
use Matomo\DataTable\Row;
use Matomo\Plugins\CustomDimensions\Archiver;

/**
 * @group CustomDimensions
 * @group AddSegmentMetadataTest
 * @group AddSegmentMetadata
 * @group Plugins
 */
class AddSegmentMetadataTest extends \PHPUnit\Framework\TestCase
{
    private $filter = 'Matomo\Plugins\CustomDimensions\DataTable\Filter\AddSegmentMetadata';

    public function testFilter()
    {
        $dataTable = new DataTable();
        $dataTable->addRowsFromArray(array(
            array(Row::COLUMNS => array('label' => 'val1', 'nb_visits' => 120)), // normal case
            array(Row::COLUMNS => array('nb_visits' => 90)), // no label should not add segment metadata
            array(Row::COLUMNS => array('label' => 'val2 5w ö?', 'nb_visits' => 99)), // should encode label
            array(Row::COLUMNS => array('label' => Archiver::LABEL_CUSTOM_VALUE_NOT_DEFINED, 'nb_visits' => 99)), // should set no label
        ));

        $dataTable->filter($this->filter, array($idDimension = 5));

        $metadata = $dataTable->getRowsMetadata('segment');

        $expected = array(
            'dimension5==val1',
            false,
            'dimension5==val2+5w+%C3%B6%3F',
            'dimension5==',
        );
        $this->assertSame($expected, $metadata);
    }
}
