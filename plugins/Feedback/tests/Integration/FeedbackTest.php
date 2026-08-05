<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Feedback\tests\Unit;

use Matomo\Container\StaticContainer;
use Matomo\Date;
use Matomo\Matomo;
use Matomo\Plugins\Feedback\API;
use Matomo\Plugins\Feedback\Feedback;
use Matomo\Plugins\UsersManager\Model;
use Matomo\Settings\Storage\Backend\PluginSettingsTable;
use Matomo\Settings\Storage\UserScopedSettingsAccessManager;
use Matomo\Tests\Framework\Mock\FakeAccess;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

class FeedbackTest extends IntegrationTestCase
{
    /** @var Feedback */
    private $feedback;

    /** @var Model */
    private $userModel;

    private $now;

    public function setUp(): void
    {
        parent::setUp();

        $this->feedback = $this->createPartialMock(Feedback::class, ['isDisabledInTestMode']);
        $this->feedback->method('isDisabledInTestMode')->willReturn(false);

        $this->userModel = new Model();
        $this->userModel->addUser(
            'user1',
            'a98732d98732',
            'user1@example.com',
            '2019-03-03'
        );

        $this->userModel->addUser(
            'user2',
            'a98732d98732',
            'user2@example.com',
            Date('Y-m-d')
        );
        FakeAccess::$identity = 'user1';
        FakeAccess::$superUser = false;
        FakeAccess::$idSitesView = [1];
        $this->now = Date::$now;
    }

    public function tearDown(): void
    {
        FakeAccess::$identity = 'user1';
        PluginSettingsTable::removeAllUserSettingsForUser('user1');
        $this->userModel->deleteUserOnly('user1');

        FakeAccess::$identity = 'user2';
        PluginSettingsTable::removeAllUserSettingsForUser('user2');
        $this->userModel->deleteUserOnly('user2');

        Date::$now = $this->now;
        parent::tearDown();
    }

    public function provideContainerConfig()
    {
        return array(
          'Matomo\Access' => new FakeAccess(),
        );
    }


    public function testShouldPromptForFeedbackAnonymousUser()
    {
        FakeAccess::$identity = '';

        $this->assertFalse($this->feedback->showQuestionBanner());
    }


    public function testShouldPromptForFeedbackDontRemindUserAgain()
    {
        $this->setFeedbackReminderForUser('user1', '-1');

        $this->assertFalse($this->feedback->showQuestionBanner());
    }

    public function testShouldPromptForFeedbackNextReminderDateInPast()
    {
        FakeAccess::$identity = 'user1';
        $this->setFeedbackReminderForUser('user1', '2019-05-31');
        $this->assertTrue($this->feedback->showQuestionBanner());
    }

    public function testShouldPromptForFeedackNextReminderDateToday()
    {
        $this->setFeedbackReminderForUser('user1', '2018-10-31');
        $this->assertTrue($this->feedback->showQuestionBanner());
    }

    public function testShouldPromptForFeedbackUserOldThanHalfYear()
    {
        FakeAccess::$identity = 'user1';
        StaticContainer::get(UserScopedSettingsAccessManager::class)->deleteAll('Feedback', 'user1');
        $this->assertFalse($this->feedback->showQuestionBanner());
    }

    public function testShouldNotPromptForFeedbackUserLessThanHalfYear()
    {
        FakeAccess::$identity = 'user2';
        $this->assertFalse($this->feedback->showQuestionBanner());
    }

    public function testShouldSendFeedbackForFeature()
    {
        $api = API::getInstance();

        //test failed without message
        $result = $api->sendFeedbackForFeature('test');
        $this->assertEquals(Matomo::translate("Feedback_FormNotEnoughFeedbackText"), $result);

        //test pass with like is string 0
        $result = $api->sendFeedbackForFeature('test', "0", null, "dislike this test");
        $this->assertEquals("success", $result);

        //test pass with like is a string 1
        $result = $api->sendFeedbackForFeature('test', "1", null, "like this test");
        $this->assertEquals("success", $result);

        //test pass with like is null
        $result = $api->sendFeedbackForFeature('test', null, null, "dislike this test");
        $this->assertEquals("success", $result);
    }

    private function setFeedbackReminderForUser(string $login, string $value): void
    {
        StaticContainer::get(UserScopedSettingsAccessManager::class)->set('Feedback', $login, 'nextFeedbackReminder', $value);
    }
}
