<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Tracker\Visit;

use Matomo\Matomo;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;
use Matomo\Tracker;
use Matomo\Tracker\Visit;
use Matomo\Tracker\Visit\Factory;

/**
 * @group Tracker
 * @group Handler
 * @group Visit
 * @group Factory
 * @group FactoryTest
 */
class FactoryTest extends IntegrationTestCase
{
    public function testMakeShouldCreateDefaultInstance()
    {
        $visit = Factory::make();
        $this->assertInstanceOf('Matomo\Tracker\Visit', $visit);
    }

    public function testMakeShouldTriggerEventOnce()
    {
        $called = 0;
        $self   = $this;
        Matomo::addAction('Tracker.makeNewVisitObject', function ($visit) use (&$called, $self) {
            $called++;
            $self->assertNull($visit);
        });

        Factory::make();
        $this->assertSame(1, $called);
    }

    public function testMakeShouldPreferManuallyCreatedHandlerInstanceInEventOverDefaultHandler()
    {
        $visitToUse = new Visit();
        Matomo::addAction('Tracker.makeNewVisitObject', function (&$visit) use ($visitToUse) {
            $visit = $visitToUse;
        });

        $visit = Factory::make();
        $this->assertSame($visitToUse, $visit);
    }

    public function testMakeShouldTriggerExceptionInCaseWrongInstanceCreatedInHandler()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The Visit object set in the plugin');

        Matomo::addAction('Tracker.makeNewVisitObject', function (&$visit) {
            $visit = new Tracker();
        });

        Factory::make();
    }
}
