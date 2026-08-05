<?php

namespace Matomo\Plugins\UsersManager\Emails;

use Matomo\Common;
use Matomo\Config;
use Matomo\IP;
use Matomo\Mail;
use Matomo\Matomo;
use Matomo\View;

class UserInfoChangedEmail extends Mail
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $changedNewValue;

    /**
     * @var string
     */
    private $deviceDescription;

    /**
     * @var string
     */
    private $login;

    public function __construct($type, $changedNewValue, $deviceDescription, $login)
    {
        parent::__construct();
        $this->type = $type;
        $this->changedNewValue = $changedNewValue;
        $this->deviceDescription = $deviceDescription;
        $this->login = $login;
        $this->setUpEmail();
    }


    private function setUpEmail()
    {
        $this->setDefaultFromPiwik();
        $this->setWrappedHtmlBody($this->getDefaultBodyView());

        $replytoEmailName = Config::getInstance()->General['login_password_recovery_replyto_email_name'];
        $replytoEmailAddress = Config::getInstance()->General['login_password_recovery_replyto_email_address'];
        $this->addReplyTo($replytoEmailAddress, $replytoEmailName);
    }


    /**
     * @return View
     */
    protected function getDefaultBodyView()
    {
        $deviceDescription = $this->deviceDescription;

        $view = new View('@UsersManager/_userInfoChangedEmail.twig');
        $view->type = $this->type;
        $view->accountName = Common::sanitizeInputValue($this->login);
        $view->newEmail = Common::sanitizeInputValue($this->changedNewValue);
        $view->changeBySuperUser = $this->login !== Matomo::getCurrentUserLogin();
        $view->ipAddress = IP::getIpFromHeader();
        $view->deviceDescription = $deviceDescription;
        return $view;
    }
}
