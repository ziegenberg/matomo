<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Events\Columns;

use Matomo\Columns\Discriminator;
use Matomo\Columns\Join\ActionNameJoin;
use Matomo\Matomo;
use Matomo\Plugin\Dimension\ActionDimension;
use Matomo\Tracker\Action;
use Matomo\Tracker\TableLogAction;

class EventUrl extends ActionDimension
{
    protected $columnName = 'idaction_url';
    protected $segmentName = 'eventUrl';
    protected $nameSingular = 'Events_EventUrl';
    protected $namePlural = 'Events_EventUrls';
    protected $type = self::TYPE_URL;
    protected $category = 'Events_Events';
    protected $sqlFilter = [TableLogAction::class, 'getOptimizedIdActionSqlMatch'];

    public function getAcceptValues()
    {
        return Matomo::translate('Events_EventUrlSegmentHelp', 'http%3A%2F%2Fexample.com%2Fpath%2Fpage%3Fquery');
    }

    public function getDbColumnJoin()
    {
        return new ActionNameJoin();
    }

    public function getDbDiscriminator()
    {
        return new Discriminator('log_action', 'type', Action::TYPE_EVENT);
    }
}
