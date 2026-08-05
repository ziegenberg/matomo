<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Live\Widgets;

use Matomo\Common;
use Matomo\Matomo;
use Matomo\Plugins\Live\Live;
use Matomo\Widget\WidgetConfig;

class GetVisitorProfilePopup extends \Matomo\Widget\Widget
{
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('General_Visitors');
        $config->setName('Live_VisitorProfile');
        $config->setOrder(25);

        if (Matomo::isUserIsAnonymous()) {
            $config->disable();
        }

        $idSite = Common::getRequestVar('idSite', 0, 'int');

        if (empty($idSite)) {
            return;
        }

        if (!Live::isVisitorProfileEnabled($idSite)) {
            $config->disable();
        }
    }
}
