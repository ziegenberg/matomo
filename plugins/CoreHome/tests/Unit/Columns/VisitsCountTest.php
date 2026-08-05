<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\tests\Unit\Columns;

use PHPUnit\Framework\TestCase;
use Matomo\Plugins\CoreHome\Columns\VisitsCount;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visit\VisitProperties;
use Matomo\Tracker\Visitor;

class VisitsCountTest extends TestCase
{
    public function testOnNewVisitReturnsZeroIfPreviousVisitCountDoesNotExist()
    {
        $request = $this->makeMockRequest();
        $visitor = $this->makeMockVisitor(false);

        $dim = new VisitsCount();
        $visitCount = $dim->onNewVisit($request, $visitor, null);

        $this->assertEquals(1, $visitCount);
    }

    public function testOnNewVisitReturnsIncrementedValueIfPreviousVisitCountIsPresent()
    {
        $request = $this->makeMockRequest();
        $visitor = $this->makeMockVisitor(false, [], ['visitor_count_visits' => 10]);

        $dim = new VisitsCount();
        $visitCount = $dim->onNewVisit($request, $visitor, null);

        $this->assertEquals(11, $visitCount);
    }

    /**
     * @return Request
     */
    private function makeMockRequest($currentTime = null)
    {
        $mockBuilder = $this->getMockBuilder(Request::class)->disableOriginalConstructor();
        if ($currentTime) {
            $mockBuilder->onlyMethods(['getCurrentTimestamp']);
        }
        $mock = $mockBuilder->getMock();
        if ($currentTime) {
            $mock->method('getCurrentTimestamp')->willReturn($currentTime);
        }

        /** @var Request $mock */
        $result = $mock;
        return $result;
    }

    private function makeMockVisitor($isKnown, $visitProps = [], $previousVisitProps = [])
    {
        $visitor = new Visitor(new VisitProperties($visitProps), $isKnown, new VisitProperties($previousVisitProps));
        return $visitor;
    }
}
