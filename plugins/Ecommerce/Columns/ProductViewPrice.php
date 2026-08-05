<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Columns;

use Matomo\Plugin\Dimension\ActionDimension;
use Matomo\Plugin\Manager;
use Matomo\Plugins\CustomVariables\Tracker\CustomVariablesRequestProcessor;
use Matomo\Tracker\Action;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visitor;

class ProductViewPrice extends ActionDimension
{
    protected $type = self::TYPE_MONEY;
    protected $nameSingular = 'Ecommerce_ViewedProductPrice';
    protected $columnName = 'product_price';
    protected $segmentName = 'productViewPrice';
    protected $columnType = 'DOUBLE NULL';
    protected $category = 'Goals_Ecommerce';

    public function onNewAction(Request $request, Visitor $visitor, Action $action)
    {
        $price = $request->getParam('_pkp');
        if (is_numeric($price)) {
            return $price;
        }

        // fall back to custom variables (might happen if old logs are replayed)
        if (Manager::getInstance()->isPluginActivated('CustomVariables')) {
            $customVariables = CustomVariablesRequestProcessor::getCustomVariablesInPageScope($request);
            if (isset($customVariables['custom_var_k2']) && $customVariables['custom_var_k2'] === '_pkp') {
                return $customVariables['custom_var_v2'] ?? false;
            }
        }

        return parent::onNewAction($request, $visitor, $action);
    }
}
