<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreAdminHome\Emails;

use Matomo\Matomo;
use Matomo\Plugins\CoreAdminHome\Emails\SecurityNotificationEmail;

class SettingsChangedEmail extends SecurityNotificationEmail
{
    /**
     * @var string
     */
    private $superuser;

    /**
     * @var string
     */
    private $pluginNames;

    public function __construct($login, $emailAddress, $pluginNames, $superuser = null)
    {
        $this->pluginNames = $pluginNames;
        $this->superuser = $superuser;

        parent::__construct($login, $emailAddress);
    }

    protected function getBody()
    {
        if ($this->superuser) {
            return Matomo::translate('CoreAdminHome_SecurityNotificationSettingsChangedByOtherSuperUserBody', [$this->superuser, $this->pluginNames]);
        }

        return Matomo::translate('CoreAdminHome_SecurityNotificationSettingsChangedByUserBody', [$this->pluginNames]) . ' ' . Matomo::translate('UsersManager_IfThisWasYouPasswordChange');
    }
}
