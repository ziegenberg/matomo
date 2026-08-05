<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Actions\Columns;

use Matomo\Plugin\Dimension\ActionDimension;
use Matomo\Plugins\Actions\Actions\ActionSiteSearch;
use Matomo\Tracker\Action;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visitor;

class SearchCount extends ActionDimension
{
    protected $type = self::TYPE_TEXT;
    protected $nameSingular = 'Actions_SiteSearchKeywordCount';
    protected $namePlural = 'Actions_SiteSearchKeywordCounts';
    protected $columnName = 'search_count';
    protected $segmentName = 'siteSearchCount';
    protected $columnType = 'INTEGER(10) UNSIGNED NULL';

    public function onNewAction(Request $request, Visitor $visitor, Action $action)
    {
        if ($action instanceof ActionSiteSearch) {
            return $action->getSearchCount();
        }

        return parent::onNewAction($request, $visitor, $action);
    }
}
