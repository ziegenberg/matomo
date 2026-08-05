<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\ProfessionalServices;

use Matomo\Matomo;
use Matomo\Plugins\ProfessionalServices\Widgets\DismissibleWidget;
use Matomo\Request;

/**
 * Provides API methods for Professional Services widgets and prompts.
 *
 * @method static \Matomo\Plugins\ProfessionalServices\API getInstance()
 */
class API extends \Matomo\Plugin\API
{
    private PromoWidgetDismissal $promoWidgetDismissal;

    public function __construct(PromoWidgetDismissal $promoWidgetDismissal)
    {
        $this->promoWidgetDismissal = $promoWidgetDismissal;
    }

    /**
     * Dismisses a Professional Services promo widget for the current user.
     *
     * @internal
     * @return bool Returns `true` when the widget dismissal was recorded.
     */
    public function dismissWidget(): bool
    {
        Matomo::checkUserIsNotAnonymous();

        $widgetName = Request::fromRequest()->getStringParameter('widgetName');

        if (!DismissibleWidget::exists($widgetName)) {
            throw new \Exception('Can\'t dismiss unknown widget ' . $widgetName);
        }

        $this->promoWidgetDismissal->dismissPromoWidget($widgetName);

        return true;
    }
}
