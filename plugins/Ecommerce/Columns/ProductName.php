<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Columns;

use Matomo\Columns\Dimension;
use Matomo\Columns\Discriminator;
use Matomo\Columns\Join\ActionNameJoin;
use Matomo\Tracker\Action;
use Matomo\Tracker\TableLogAction;

class ProductName extends Dimension
{
    protected $type = self::TYPE_TEXT;
    protected $dbTableName = 'log_conversion_item';
    protected $columnName = 'idaction_name';
    protected $nameSingular = 'Goals_ProductName';
    protected $namePlural = 'Goals_ProductNames';
    protected $category = 'Goals_Ecommerce';
    protected $segmentName = 'productName';
    protected $sqlFilter = [TableLogAction::class, 'getOptimizedIdActionSqlMatch'];

    public function getDbColumnJoin()
    {
        return new ActionNameJoin();
    }

    public function getDbDiscriminator()
    {
        return new Discriminator('log_action', 'type', Action::TYPE_ECOMMERCE_ITEM_NAME);
    }
}
