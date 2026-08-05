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

class PageUrl extends ActionDimension
{
    protected $columnName = 'idaction_url';
    protected $columnType = 'INTEGER(10) UNSIGNED DEFAULT NULL';
    protected $segmentName = 'pageUrl';
    protected $nameSingular = 'Actions_ColumnPageURL';
    protected $namePlural = 'Actions_PageUrls';
    protected $type = self::TYPE_URL;
    protected $category = 'General_Actions';
    protected $suggestedValuesApi = 'Actions.getPageUrls';
    protected $sqlFilter = [TableLogAction::class, 'getOptimizedIdActionSqlMatch'];

    public function getDbColumnJoin()
    {
        return new ActionNameJoin();
    }

    public function getDbDiscriminator()
    {
        return new Discriminator('log_action', 'type', Action::TYPE_PAGE_URL);
    }
}
