<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Feedback;

use Matomo\Container\StaticContainer;
use Matomo\Matomo;
use Matomo\Settings\Storage\UserScopedSettingsAccessManager;

class FeedbackReminder
{
    public $userLogin;

    public function __construct()
    {
        $this->userLogin = Matomo::getCurrentUserLogin();
    }

    public function getUserOption()
    {
        return $this->getAccessManager()->get('Feedback', $this->userLogin, 'nextFeedbackReminder', false);
    }

    public function setUserOption($value)
    {
        $this->getAccessManager()->set('Feedback', $this->userLogin, 'nextFeedbackReminder', $value);
    }

    private function getAccessManager(): UserScopedSettingsAccessManager
    {
        return StaticContainer::get(UserScopedSettingsAccessManager::class);
    }
}
