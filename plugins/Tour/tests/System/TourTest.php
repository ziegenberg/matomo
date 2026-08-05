<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Tour\tests\System;

use Matomo\API\Request;
use Matomo\Container\StaticContainer;
use Matomo\Date;
use Matomo\Matomo;
use Matomo\Plugins\Tour\Engagement\ChallengeAddedAnnotation;
use Matomo\Plugins\Tour\Engagement\ChallengeInvitedUser;
use Matomo\Plugins\Tour\Engagement\ChallengeCreatedGoal;
use Matomo\Plugins\Tour\tests\Fixtures\SimpleFixtureTrackFewVisits;
use Matomo\Tests\Framework\TestCase\SystemTestCase;

/**
 * @group Tour
 * @group TourTest
 * @group Plugins
 */
class TourTest extends SystemTestCase
{
    /**
     * @var SimpleFixtureTrackFewVisits
     */
    public static $fixture = null; // initialized below class definition

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testHasCreatedGoal()
    {
        $goal = StaticContainer::get(ChallengeCreatedGoal::class);

        $this->assertFalse($goal->isCompleted(Matomo::getCurrentUserLogin()));

        Request::processRequest('Goals.addGoal', array(
            'idSite' => self::$fixture->idSite, 'name' => 'MyGoal', 'matchAttribute' => 'url', 'pattern' => 'foobar', 'patternType' => 'contains',
        ));

        $this->assertTrue($goal->isCompleted(Matomo::getCurrentUserLogin()));
    }

    public function testHasAddedUser()
    {
        $user = StaticContainer::get(ChallengeInvitedUser::class);
        $this->assertFalse($user->isCompleted(Matomo::getCurrentUserLogin()));

        Request::processRequest('UsersManager.inviteUser', array('userLogin' => 'myerwerwer', 'email' => 'tesr@matomo.org', 'initialIdSite' => 1));

        $this->assertTrue($user->isCompleted(Matomo::getCurrentUserLogin()));
    }

    public function testHasAddedAnnotation()
    {
        $annotation = StaticContainer::get(ChallengeAddedAnnotation::class);
        $this->assertFalse($annotation->isCompleted(Matomo::getCurrentUserLogin()));

        Request::processRequest('Annotations.add', array(
            'idSite' => self::$fixture->idSite, 'date' => Date::now()->getDatetime(), 'note' => 'foo bar'));

        $this->assertTrue($annotation->isCompleted(Matomo::getCurrentUserLogin()));
    }

    public static function getOutputPrefix()
    {
        return '';
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}

TourTest::$fixture = new SimpleFixtureTrackFewVisits();
