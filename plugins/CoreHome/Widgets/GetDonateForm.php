<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Widgets;

use Matomo\Common;
use Matomo\Matomo;
use Matomo\Widget\Widget;
use Matomo\Widget\WidgetConfig;
use Matomo\Translation\Translator;

class GetDonateForm extends Widget
{
    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('About Matomo');
        $config->setName('CoreHome_SupportPiwik');
        $config->setOrder(5);
    }

    public function render()
    {
        $footerMessage = null;
        if (
            Common::getRequestVar('widget', false)
            && Matomo::hasUserSuperUserAccess()
        ) {
            $footerMessage = $this->translator->translate('CoreHome_OnlyForSuperUserAccess');
        }

        return $this->renderTemplate('getDonateForm', array(
            'footerMessage' => $footerMessage,
        ));
    }
}
