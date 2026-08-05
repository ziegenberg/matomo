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

class TokenAuthCreatedEmail extends SecurityNotificationEmail
{
    /**
     * @var string
     */
    private $tokenDescription;

    public function __construct($login, $emailAddress, $tokenDescription)
    {
        $this->tokenDescription = $tokenDescription;

        parent::__construct($login, $emailAddress);
    }

    protected function getBody()
    {
        return Matomo::translate('CoreAdminHome_SecurityNotificationTokenAuthCreatedBody', [$this->tokenDescription]) . ' ' . Matomo::translate('UsersManager_IfThisWasYouPasswordChange');
    }
}
