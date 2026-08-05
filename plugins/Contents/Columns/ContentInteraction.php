<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Contents\Columns;

use Matomo\Columns\Discriminator;
use Matomo\Columns\Join\ActionNameJoin;
use Matomo\Plugin\Dimension\ActionDimension;
use Matomo\Plugins\Contents\Actions\ActionContent;
use Matomo\Tracker\Action;
use Matomo\Tracker\Request;
use Matomo\Tracker\TableLogAction;

class ContentInteraction extends ActionDimension
{
    protected $columnName = 'idaction_content_interaction';
    protected $columnType = 'INTEGER(10) UNSIGNED DEFAULT NULL';
    protected $type = self::TYPE_TEXT;
    protected $acceptValues = 'Contents_ContentInteractionSegmentHelp';
    protected $segmentName = 'contentInteraction';
    protected $nameSingular = 'Contents_ContentInteraction';
    protected $namePlural = 'Contents_ContentInteractions';
    protected $category = 'General_Actions';
    protected $sqlFilter = [TableLogAction::class, 'getOptimizedIdActionSqlMatch'];

    public function getDbColumnJoin()
    {
        return new ActionNameJoin();
    }

    public function getDbDiscriminator()
    {
        return new Discriminator('log_action', 'type', $this->getActionId());
    }

    public function getActionId()
    {
        return Action::TYPE_CONTENT_INTERACTION;
    }

    public function onLookupAction(Request $request, Action $action)
    {
        if (!($action instanceof ActionContent)) {
            return false;
        }

        $interaction = $request->getParam('c_i');
        $interaction = trim($interaction);

        if (strlen($interaction) > 0) {
            return $interaction;
        }

        return false;
    }
}
