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

class TokenAuthDeletedEmail extends SecurityNotificationEmail
{
    /**
     * @var string
     */
    private $tokenDescription;

    /**
     * @var bool
     */
    private $all;

    public function __construct($login, $emailAddress, $tokenDescription, $all = false)
    {
        $this->tokenDescription = $tokenDescription;
        $this->all = $all;

        parent::__construct($login, $emailAddress);
    }

    protected function getBody()
    {
        if ($this->all) {
            return Matomo::translate('CoreAdminHome_SecurityNotificationAllTokenAuthDeletedBody') . ' ' . Matomo::translate('UsersManager_IfThisWasYouPasswordChange');
        }

        return Matomo::translate('CoreAdminHome_SecurityNotificationTokenAuthDeletedBody', [$this->tokenDescription]) . ' ' . Matomo::translate('UsersManager_IfThisWasYouPasswordChange');
    }
}
