<?php

namespace Matomo\Plugins\Feedback\tests\Fixtures;

use Matomo\Date;
use Matomo\Settings\Storage\Backend\PluginSettingsTable;
use Matomo\Settings\Storage\UserScopedSettingsAccessManager;
use Matomo\Tests\Fixtures\UITestFixture;

class FeedbackQuestionBannerFixture extends UITestFixture
{
    public function setUp(): void
    {
        parent::setUp();
        $yesterday = Date::yesterday();
        (new UserScopedSettingsAccessManager())->set('Feedback', 'superUserLogin', 'nextFeedbackReminder', $yesterday->toString('Y-m-d'));
    }

    public function tearDown(): void
    {
        parent::tearDown();
        PluginSettingsTable::removeAllUserSettingsForUser('superUserLogin');
    }
}
