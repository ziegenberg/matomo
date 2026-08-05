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

class RecoveryCodesRegeneratedEmail extends SecurityNotificationEmail
{
    protected function getBody()
    {
        return Matomo::translate('CoreAdminHome_SecurityNotificationRecoveryCodesRegeneratedBody') . ' ' . Matomo::translate('UsersManager_IfThisWasYouPasswordChange');
    }
}
