<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\Referrers\DataTable\Filter;

use Matomo\DataTable\BaseFilter;
use Matomo\DataTable;
use Matomo\Plugins\Actions\ArchivingHelper;
use Matomo\Tracker\Action;

class UrlsForAIAssistant extends BaseFilter
{
    /**
     * @param DataTable $table
     */
    public function filter($table)
    {
        // make url labels clickable
        $table->filter('ColumnCallbackAddMetadata', array('label', 'url'));

        // prettify the DataTable
        $table->filter('ColumnCallbackReplace', array('label', 'Matomo\Plugins\Referrers\removeUrlProtocol'));
        $table->filter(function (DataTable $table) {
            $emptyUrlRRow = $table->getRowFromLabel('');

            if ($emptyUrlRRow) {
                $emptyUrlRRow->setColumn('label', ArchivingHelper::getUnknownActionName(Action::TYPE_PAGE_URL));
            }
        });
        $table->queueFilter('ReplaceColumnNames');
    }
}
