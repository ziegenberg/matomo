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

class ExitPageTitle extends VisitDimension
{
    protected $columnName = 'visit_exit_idaction_name';
    protected $columnType = 'INTEGER(10) UNSIGNED NULL';
    protected $segmentName = 'exitPageTitle';
    protected $nameSingular = 'Actions_ColumnExitPageTitle';
    protected $namePlural = 'Actions_WidgetExitPageTitles';
    protected $category = 'General_Actions';
    protected $suggestedValuesApi = 'Actions.getExitPageTitles';
    protected $type = self::TYPE_TEXT;
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
     * @return int|bool
     */
    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        $idActionName = false;

        if (!empty($action)) {
            $idActionName = $action->getIdActionNameForEntryAndExitIds();
        }

        return (int) $idActionName;
    }

    /**
     * @param Action|null $action
     * @return int|false|null
     */
    public function onExistingVisit(Request $request, Visitor $visitor, $action)
    {
        if (empty($action)) {
            return false;
        }

        return $action->getIdActionNameForEntryAndExitIds();
    }
}
