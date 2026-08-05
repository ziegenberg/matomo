<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Login\tests\Integration;

use Matomo\Access;
use Matomo\Container\StaticContainer;
use Matomo\Plugins\Login\PasswordVerifier;
use Matomo\Plugins\UsersManager\API as UsersAPI;
use Matomo\Plugins\UsersManager\UserUpdater;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * Verifies that checking a password does not change the current authentication state.
 *
 * @group Login
 */
class PasswordVerifierAuthStateTest extends IntegrationTestCase
{
    private const OTHER_USER = 'anothersuperuser';
    private const OTHER_USER_PASSWORD = '123abcDk3_l3';

    public function setUp(): void
    {
        parent::setUp();

        UsersAPI::getInstance()->addUser(self::OTHER_USER, self::OTHER_USER_PASSWORD, self::OTHER_USER . '@matomo.org');
        (new UserUpdater())->setSuperUserAccessWithoutCurrentPassword(self::OTHER_USER, true);
    }

    public function testCheckingAPasswordDoesNotChangeTheAuthenticatedUser()
    {
        $this->authenticateAsAnonymous();
        $this->assertFalse(Access::getInstance()->hasSuperUserAccess());
        $this->assertNull(Access::getInstance()->getLogin());

        // Check another user's password.
        $isCorrect = StaticContainer::get(PasswordVerifier::class)
            ->isPasswordCorrect(self::OTHER_USER, self::OTHER_USER_PASSWORD);
        $this->assertTrue($isCorrect);

        // The current user must be unchanged afterwards.
        Access::getInstance()->reloadAccess();

        $this->assertFalse(Access::getInstance()->hasSuperUserAccess());
        $this->assertNull(Access::getInstance()->getLogin());
    }

    private function authenticateAsAnonymous(): void
    {
        Access::getInstance()->setSuperUserAccess(false);

        /** @var \Matomo\Auth $auth */
        $auth = StaticContainer::get('Matomo\Auth');
        $auth->setLogin('anonymous');
        $auth->setTokenAuth('anonymous');
        $auth->setPasswordHash(null);

        Access::getInstance()->reloadAccess($auth);
    }
}
