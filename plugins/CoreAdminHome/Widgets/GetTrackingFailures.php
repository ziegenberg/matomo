<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreAdminHome\Widgets;

use Matomo\API\Request;
use Matomo\Matomo;
use Matomo\Widget\Widget;
use Matomo\Widget\WidgetConfig;

class GetTrackingFailures extends Widget
{
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('About Matomo');
        $config->setName('CoreAdminHome_TrackingFailures');
        $config->setOrder(5);

        if (!Matomo::isUserHasSomeAdminAccess()) {
            $config->disable();
        }
    }

    public function render()
    {
        Matomo::checkUserHasSomeAdminAccess();
        $failures = Request::processRequest('CoreAdminHome.getTrackingFailures');
        $numFailures = count($failures);

        return $this->renderTemplate('getTrackingFailures', array(
            'numFailures' => $numFailures,
        ));
    }
}
