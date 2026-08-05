<?php

namespace Matomo\Plugins\UsersManager\Repository;

use Matomo\Auth\Password;
use Matomo\Container\StaticContainer;
use Matomo\Date;
use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugin;
use Matomo\Plugins\CoreAdminHome\Emails\UserCreatedEmail;
use Matomo\Plugins\UsersManager\API;
use Matomo\Plugins\UsersManager\Emails\UserInviteEmail;
use Matomo\Plugins\UsersManager\Model;
use Matomo\Plugins\UsersManager\UserAccessFilter;
use Matomo\Plugins\UsersManager\UsersManager;
use Matomo\Plugins\UsersManager\Validators\AllowedEmailDomain;
use Matomo\Plugins\UsersManager\Validators\Email;
use Matomo\Plugins\UsersManager\Validators\Login;
use Matomo\Site;
use Matomo\Validators\BaseValidator;

class UserRepository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var UserAccessFilter
     */
    protected $filter;

    /**
     * @var Password
     */
    protected $password;

    /**
     * @var AllowedEmailDomain
     */
    protected $allowedEmailDomain;

    /**
     * @var ?bool
     */
    private $twoFaPluginActivated = null;

    public function __construct(Model $model, UserAccessFilter $filter, Password $password, AllowedEmailDomain $allowedEmailDomain)
    {
        $this->model = $model;
        $this->filter = $filter;
        $this->password = $password;
        $this->allowedEmailDomain = $allowedEmailDomain;
    }

    /**
     * @throws \Exception
     */
    public function create(
        string $userLogin,
        string $email,
        ?int $initialIdSite = null,
        #[\SensitiveParameter]
        string $password = '',
        bool $isPasswordHashed = false
    ): void {


        if (!Matomo::hasUserSuperUserAccess()) {
            // check if the user has admin access to the site
            Matomo::checkUserHasAdminAccess($initialIdSite);
        }

        BaseValidator::check(Matomo::translate('General_Username'), $userLogin, [new Login(true)]);
        BaseValidator::check(Matomo::translate('Installation_Email'), $email, [new Email(true), $this->allowedEmailDomain]);

        if (!empty($password)) {
            if (!$isPasswordHashed) {
                $passwordTransformed = UsersManager::getPasswordHash($password);
            } else {
                $passwordTransformed = $password;
            }
            $password = $this->password->hash($passwordTransformed);
        }

        $this->model->addUser($userLogin, $password, $email, Date::now()->getDatetime());

        if ($initialIdSite) {
            API::getInstance()->setUserAccess($userLogin, 'view', $initialIdSite);
        }

        $this->sendUserCreationNotification($userLogin);
    }

    public function inviteUser(string $userLogin, string $email, ?int $initialIdSite = null, $expiryInDays = null): void
    {
        $this->create($userLogin, $email, $initialIdSite);
        $this->model->updateUserFields($userLogin, ['invited_by' => Matomo::getCurrentUserLogin()]);
        $user = $this->model->getUser($userLogin);
        $generatedToken = $this->model->generateRandomInviteToken();
        $this->model->attachInviteToken($userLogin, $generatedToken, $expiryInDays);
        $this->sendInvitationEmail($user, $generatedToken, $expiryInDays);
    }

    public function reInviteUser(string $userLogin, int $expiryInDays): void
    {
        $user = $this->model->getUser($userLogin);
        $generatedToken = $this->model->generateRandomInviteToken();
        $this->model->attachInviteToken($userLogin, $generatedToken, $expiryInDays);
        $this->sendInvitationEmail($user, $generatedToken, $expiryInDays);
    }

    public function generateInviteToken(string $userLogin, int $expiryInDays): string
    {
        $generatedToken = $this->model->generateRandomInviteToken();
        $this->model->attachInviteLinkToken($userLogin, $generatedToken, $expiryInDays);
        return $generatedToken;
    }

    protected function sendUserCreationNotification(string $createdUserLogin): void
    {
        if (Matomo::getCurrentUserLogin() !== 'anonymous' && Matomo::getCurrentUserEmail() !== '') {
            $mail = StaticContainer::getContainer()->make(UserCreatedEmail::class, [
                'login' => Matomo::getCurrentUserLogin(),
                'emailAddress' => Matomo::getCurrentUserEmail(),
                'userLogin' => $createdUserLogin,
            ]);
            $mail->safeSend();
        }
    }

    protected function sendInvitationEmail(array $user, string $inviteToken, int $expiryInDays): void
    {
        $site = $this->model->getSitesAccessFromUser($user['login']);

        if (isset($site[0])) {
            $siteName = Site::getNameFor($site[0]['site']);
        } else {
            $siteName = "Default Site";
        }

        $email = StaticContainer::getContainer()->make(UserInviteEmail::class, [
            'currentUser'  => Matomo::getCurrentUserLogin(),
            'invitedUser'  => $user,
            'siteName'     => $siteName,
            'token'        => $inviteToken,
            'expiryInDays' => $expiryInDays,
        ]);
        $email->safeSend();
    }

    /**
     * @param array $user
     * @return array
     */
    public function enrichUser(array $user): array
    {
        if (empty($user)) {
            return $user;
        }

        unset($user['token_auth']);
        unset($user['password']);
        unset($user['ts_password_modified']);
        unset($user['idchange_last_viewed']);
        unset($user['ts_changes_shown']);
        unset($user['invite_token']);
        unset($user['invite_link_token']);
        unset($user['ts_inactivity_notified']);

        if (isset($user['ts_last_seen'])) {
            $formatter = new Formatter();
            $user['last_seen'] = $user['ts_last_seen'];
            $user['last_seen_ago'] = $formatter->getPrettyTimeFromSeconds(
                time() - Date::factory($user['ts_last_seen'])->getTimestamp()
            );
        }
        unset($user['ts_last_seen']);

        $user['invite_status'] = 'active';

        if (!empty($user['invite_expired_at'])) {
            try {
                $inviteExpireAt = Date::factory($user['invite_expired_at']);
            } catch (\Exception $e) {
                // invite_expired_at is not a valid, in-range date (e.g. corrupted by a bug in the past);
                // treat the invite as expired instead of letting the exception break the whole user list
                $inviteExpireAt = null;
                $user['invite_status'] = 'expired';
            }

            if ($inviteExpireAt) {
                // if token expired
                if (Date::now()->isLater($inviteExpireAt)) {
                    $user['invite_status'] = 'expired';
                }
                // if token not expired
                if (Date::now()->isEarlier($inviteExpireAt)) {
                    $dayLeft = floor(Date::secondsToDays($inviteExpireAt->getTimestamp() - Date::now()->getTimestamp()));
                    $user['invite_status'] = $dayLeft;
                }
            }
        }

        if (Matomo::hasUserSuperUserAccess()) {
            $user['uses_2fa'] = !empty($user['twofactor_secret']) && $this->isTwoFactorAuthPluginEnabled();
            unset($user['twofactor_secret']);
            return $user;
        }

        $newUser = ['login' => $user['login']];

        if ($user['login'] === Matomo::getCurrentUserLogin() || !empty($user['superuser_access'])) {
            $newUser['email'] = $user['email'];
        }

        if (isset($user['role'])) {
            $newUser['role'] = $user['role'] == 'superuser' ? 'admin' : $user['role'];
        }
        if (isset($user['capabilities'])) {
            $newUser['capabilities'] = $user['capabilities'];
        }

        if (isset($user['superuser_access'])) {
            $newUser['superuser_access'] = $user['superuser_access'];
        }

        if (isset($user['last_seen'])) {
            $newUser['last_seen'] = $user['last_seen'];
        }
        $newUser['invite_status'] = $user['invite_status'];
        if (isset($user['invited_by'])) {
            $newUser['invited_by'] = $user['invited_by'];
        }

        return $newUser;
    }

    /**
     * @param array $users
     * @return array
     * @throws \Exception
     */
    public function enrichUsers(array $users): array
    {
        if (!empty($users)) {
            foreach ($users as $index => $user) {
                $users[$index] = $this->enrichUser($user);
            }
        }
        return $users;
    }

    private function isTwoFactorAuthPluginEnabled(): bool
    {
        if (!isset($this->twoFaPluginActivated)) {
            $this->twoFaPluginActivated = Plugin\Manager::getInstance()->isPluginActivated('TwoFactorAuth');
        }
        return $this->twoFaPluginActivated;
    }
}
