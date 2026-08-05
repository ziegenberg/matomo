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

class DownloadUrl extends ActionDimension
{
    protected $segmentName = 'downloadUrl';
    protected $nameSingular = 'Actions_ColumnDownloadURL';
    protected $namePlural = 'Actions_ColumnDownloadURLs';
    protected $columnName = 'idaction_url';
    protected $category = 'General_Actions';
    protected $suggestedValuesApi = 'Actions.getDownloads';
    protected $type = self::TYPE_URL;
    protected $sqlFilter = [TableLogAction::class, 'getOptimizedIdActionSqlMatch'];

    public function getDbColumnJoin()
    {
        return new ActionNameJoin();
    }

    public function getDbDiscriminator()
    {
        return new Discriminator('log_action', 'type', Action::TYPE_DOWNLOAD);
    }
}
