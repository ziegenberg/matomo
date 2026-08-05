<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UsersManager\Emails;

use Matomo\Mail;
use Matomo\Matomo;
use Matomo\Plugins\LanguagesManager\LanguagesHelper;
use Matomo\Plugins\UsersManager\Model;
use Matomo\Plugins\UsersManager\UserNotifications\UserNotification;
use Matomo\SettingsPiwik;
use Matomo\Url;
use Matomo\View;

class InactiveUsersNotificationEmail extends Mail
{
    private UserNotification $notification;

    private string $recipient;

    private array $emailData;

    private Model $userModel;

    public function __construct(UserNotification $notification, string $recipient, array $emailData)
    {
        parent::__construct();

        $this->notification = $notification;
        $this->recipient = $recipient;
        $this->emailData = $emailData;
        $this->userModel = new Model();

        $this->setUpEmail();
    }

    private function setUpEmail(): void
    {
        LanguagesHelper::doWithUserLanguage($this->recipient, function () {
            $this->setDefaultFromPiwik();
            $this->addTo($this->recipient);
            $this->setSubject($this->getDefaultSubject());
            $this->addReplyTo($this->getFrom(), $this->getFromName());
            $this->setBodyText($this->getDefaultBodyText());
            $this->setWrappedHtmlBody($this->getDefaultBodyView());
        });
    }

    protected function getManageUsersLink(): string
    {
        return SettingsPiwik::getPiwikUrl()
            . 'index.php?'
            . Url::getQueryStringFromParameters(['module' => 'UsersManager', 'action' => 'index']);
    }

    protected function getDefaultSubject(): string
    {
        return Matomo::translate('UsersManager_InactiveUsersNotificationEmailSubject');
    }

    protected function getDefaultBodyText(): string
    {
        $view = new View('@UsersManager/_inactiveUsersNotificationTextEmail.twig');
        $view->setContentType('text/plain');

        $this->assignCommonParameters($view);

        return $view->render();
    }

    protected function getDefaultBodyView(): View
    {
        $view = new View('@UsersManager/_inactiveUsersNotificationHtmlEmail.twig');

        $this->assignCommonParameters($view);

        return $view;
    }

    protected function assignCommonParameters(View $view): void
    {
        $view->inactiveUsers = $this->notification->getUsers();
        $view->manageUsersLink = $this->getManageUsersLink();
        $view->superuserLogin = $this->userModel->getUserByEmail($this->recipient)['login'];

        foreach ($this->emailData as $item => $value) {
            $view->assign($item, $value);
        }
    }
}
