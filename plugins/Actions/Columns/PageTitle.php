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
use Matomo\Plugin\Dimension\ActionDimension;
use Matomo\Tracker\Action;
use Matomo\Tracker\TableLogAction;

class PageTitle extends ActionDimension
{
    protected $columnName = 'idaction_name';
    protected $columnType = 'INTEGER(10) UNSIGNED';
    protected $type = self::TYPE_TEXT;
    protected $segmentName = 'pageTitle';
    protected $nameSingular = 'Goals_PageTitle';
    protected $namePlural = 'Actions_WidgetPageTitles';
    protected $category = 'General_Actions';
    protected $suggestedValuesApi = 'Actions.getPageTitles';
    protected $sqlFilter = [TableLogAction::class, 'getOptimizedIdActionSqlMatch'];

    public function getDbColumnJoin()
    {
        return new ActionNameJoin();
    }

    public function getDbDiscriminator()
    {
        return new Discriminator('log_action', 'type', Action::TYPE_PAGE_TITLE);
    }
}
