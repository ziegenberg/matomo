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

class UserAcceptInvitationEmail extends SecurityNotificationEmail
{
    /**
     * @var string
     */
    private $userLogin;

    public function __construct($login, $emailAddress, $userLogin)
    {
        $this->userLogin = $userLogin;

        parent::__construct($login, $emailAddress);
    }

    protected function getBody()
    {
        return Matomo::translate('CoreAdminHome_SecurityNotificationUserAcceptInviteBody', [$this->userLogin]);
    }
}
