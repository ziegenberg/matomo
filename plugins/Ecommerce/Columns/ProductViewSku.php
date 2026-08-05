<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Columns;

use Matomo\Columns\Discriminator;
use Matomo\Columns\Join\ActionNameJoin;
use Matomo\Common;
use Matomo\Plugin\Dimension\ActionDimension;
use Matomo\Plugin\Manager;
use Matomo\Plugins\CustomVariables\Tracker\CustomVariablesRequestProcessor;
use Matomo\Tracker\Action;
use Matomo\Tracker\Request;
use Matomo\Tracker\TableLogAction;

class ProductViewSku extends ActionDimension
{
    protected $type = self::TYPE_TEXT;
    protected $nameSingular = 'Ecommerce_ViewedProductSKU';
    protected $columnName = 'idaction_product_sku';
    protected $segmentName = 'productViewSku';
    protected $columnType = 'INT(10) UNSIGNED NULL';
    protected $category = 'Goals_Ecommerce';
    protected $sqlFilter = [TableLogAction::class, 'getOptimizedIdActionSqlMatch'];

    public function getDbColumnJoin()
    {
        return new ActionNameJoin();
    }

    public function getDbDiscriminator()
    {
        return new Discriminator('log_action', 'type', Action::TYPE_ECOMMERCE_ITEM_SKU);
    }

    public function onLookupAction(Request $request, Action $action)
    {
        if ($request->hasParam('_pks')) {
            return Common::unsanitizeInputValue($request->getParam('_pks'));
        }

        // fall back to custom variables (might happen if old logs are replayed)
        if (Manager::getInstance()->isPluginActivated('CustomVariables')) {
            $customVariables = CustomVariablesRequestProcessor::getCustomVariablesInPageScope($request);
            if (isset($customVariables['custom_var_k3']) && $customVariables['custom_var_k3'] === '_pks') {
                return $customVariables['custom_var_v3'] ?? false;
            }
        }

        return parent::onLookupAction($request, $action);
    }

    public function getActionId()
    {
        return Action::TYPE_ECOMMERCE_ITEM_SKU;
    }
}
