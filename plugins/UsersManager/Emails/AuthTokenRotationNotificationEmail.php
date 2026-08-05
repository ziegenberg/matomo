<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UsersManager\Emails;

use Matomo\Config;
use Matomo\Mail;
use Matomo\Matomo;
use Matomo\Plugins\UsersManager\TokenNotifications\TokenNotification;
use Matomo\SettingsPiwik;
use Matomo\Url;
use Matomo\View;

class AuthTokenRotationNotificationEmail extends Mail
{
    private TokenNotification $notification;

    private string $recipient;

    private array $emailData;

    public function __construct(TokenNotification $notification, string $recipient, array $emailData)
    {
        parent::__construct();

        $this->notification = $notification;
        $this->recipient = $recipient;
        $this->emailData = $emailData;

        $this->setUpEmail();
    }

    private function setUpEmail(): void
    {
        $this->setDefaultFromPiwik();
        $this->addTo($this->recipient);
        $this->setSubject($this->getDefaultSubject());
        $this->addReplyTo($this->getFrom(), $this->getFromName());
        $this->setBodyText($this->getDefaultBodyText());
        $this->setWrappedHtmlBody($this->getDefaultBodyView());
    }

    private function getRotationPeriodPretty(): string
    {
        $rotationPeriodDays = Config::getInstance()->General['auth_token_rotation_notification_days'];

        return $rotationPeriodDays . ' ' . Matomo::translate('Intl_PeriodDay' . ($rotationPeriodDays === 1 ? '' : 's'));
    }

    protected function getManageAuthTokensLink(): string
    {
        return $this->getInstanceUrl()
            . 'index.php?'
            . Url::getQueryStringFromParameters(['module' => 'UsersManager', 'action' => 'userSecurity'])
            . '#authtokens';
    }

    protected function getInstanceUrl(): string
    {
        return SettingsPiwik::getPiwikUrl();
    }

    protected function getDefaultSubject(): string
    {
        return Matomo::translate(
            'UsersManager_AuthTokenNotificationEmailSubjectAll',
            [
                $this->getInstanceUrl(),
            ]
        );
    }

    protected function getDefaultBodyText(): string
    {
        $view = new View('@UsersManager/_authTokenRotationNotificationTextEmail.twig');
        $view->setContentType('text/plain');

        $this->assignCommonParameters($view);

        return $view->render();
    }

    protected function getDefaultBodyView(): View
    {
        $view = new View('@UsersManager/_authTokenRotationNotificationHtmlEmail.twig');

        $this->assignCommonParameters($view);

        return $view;
    }

    protected function assignCommonParameters(View $view): void
    {
        $view->tokens = $this->notification->getTokens();
        $view->rotationPeriod = $this->getRotationPeriodPretty();
        $view->manageAuthTokensLink = $this->getManageAuthTokensLink();
        $view->instanceUrl = $this->getInstanceUrl();

        foreach ($this->emailData as $item => $value) {
            $view->assign($item, $value);
        }
    }
}
