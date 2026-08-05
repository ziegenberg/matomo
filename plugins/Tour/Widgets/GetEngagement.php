<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Tour\Widgets;

use Matomo\Widget\Widget;
use Matomo\Widget\WidgetConfig;
use Matomo\Matomo;

class GetEngagement extends Widget
{
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('About Matomo');
        $config->setName(Matomo::translate('Tour_BecomeMatomoExpert'));
        $config->setClientSideComponent('Tour', 'BecomeMatomoExpert');
        $config->setOrder(99);

        if (!Matomo::hasUserSuperUserAccess()) {
            $config->disable();
        }
    }
}
