<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Actions\Columns;

use Matomo\Columns\Discriminator;
use Matomo\Columns\Join\ActionNameJoin;
use Matomo\Plugin\Dimension\VisitDimension;
use Matomo\Tracker\Action;
use Matomo\Tracker\Request;
use Matomo\Tracker\TableLogAction;
use Matomo\Tracker\Visitor;

class EntryPageTitle extends VisitDimension
{
    protected $columnName = 'visit_entry_idaction_name';
    protected $columnType = 'INTEGER(10) UNSIGNED NULL';
    protected $type = self::TYPE_TEXT;
    protected $segmentName = 'entryPageTitle';
    protected $suggestedValuesApi = 'Actions.getEntryPageTitles';
    protected $nameSingular = 'Actions_ColumnEntryPageTitle';
    protected $namePlural = 'Actions_WidgetEntryPageTitles';
    protected $category = 'General_Actions';
    protected $sqlFilter = [TableLogAction::class, 'getOptimizedIdActionSqlMatch'];

    public function getDbColumnJoin()
    {
        return new ActionNameJoin();
    }

    public function getDbDiscriminator()
    {
        return new Discriminator('log_action', 'type', Action::TYPE_PAGE_TITLE);
    }

    /**
     * @param Action|null $action
     * @return mixed
     */
    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        $idActionName = false;

        if (!empty($action)) {
            $idActionName = $action->getIdActionNameForEntryAndExitIds();
        }

        return (int) $idActionName;
    }
}
