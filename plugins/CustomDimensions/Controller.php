<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CustomDimensions;

use Matomo\Common;
use Matomo\Matomo;

class Controller extends \Matomo\Plugin\ControllerAdmin
{
    public function manage()
    {
        $idSite = Common::getRequestVar('idSite');

        Matomo::checkUserHasWriteAccess($idSite);

        return $this->renderTemplate('manage', array(
            'idSite' => $this->idSite,
            'title' => Matomo::translate('CustomDimensions_CustomDimensions')));
    }
}
