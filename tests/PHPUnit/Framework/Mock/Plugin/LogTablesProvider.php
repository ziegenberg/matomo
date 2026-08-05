<?php

namespace Matomo\Tests\Framework\Mock\Plugin;

use Matomo\Plugins\CoreHome\Tracker\LogTable\Action;
use Matomo\Plugins\CoreHome\Tracker\LogTable\Conversion;
use Matomo\Plugins\CoreHome\Tracker\LogTable\ConversionItem;
use Matomo\Plugins\CoreHome\Tracker\LogTable\LinkVisitAction;
use Matomo\Plugins\CoreHome\Tracker\LogTable\Visit;

class LogTablesProvider extends \Matomo\Plugin\LogTablesProvider
{
    public function __construct()
    {
    }

    public function getAllLogTables()
    {
        return array(
            new Visit(),
            new Action(),
            new LinkVisitAction(),
            new ConversionItem(),
            new Conversion(),
            new CustomUserLogTable(),
            new OtherCustomUserLogTable(),
        );
    }
}
