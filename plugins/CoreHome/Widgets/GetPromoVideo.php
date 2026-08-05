<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Widgets;

use Matomo\Url;
use Matomo\Widget\Widget;
use Matomo\Widget\WidgetConfig;
use Matomo\Translation\Translator;
use Matomo\View;

class GetPromoVideo extends Widget
{
    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('About Matomo');
        $config->setName('Installation_Welcome');
        $config->setOrder(10);
    }

    public function render()
    {
        $view = new View('@CoreHome/getPromoVideo');
        $view->shareText     = $this->translator->translate('CoreHome_SharePiwikShort');
        $view->shareTextLong = $this->translator->translate('CoreHome_SharePiwikLong');
        $view->promoVideoUrl = Url::addCampaignParametersToMatomoLink('https://matomo.org/docs/videos/');

        return $view->render();
    }
}
