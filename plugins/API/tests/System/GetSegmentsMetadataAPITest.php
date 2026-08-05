<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\API\tests\System;

use Matomo\Cache;
use Matomo\API\Request;
use Matomo\Plugins\Live\SystemSettings;
use Matomo\Plugins\CoreHome\Columns\VisitorId;
use Matomo\Tests\Framework\TestCase\SystemTestCase;

class GetSegmentsMetadataAPITest extends SystemTestCase
{
    public function testItContainsVisitidByDefault()
    {
        $request = new Request([
            'method' => 'API.getSegmentsMetadata',
            'filter_limit' => -1,
            '_hideImplementationData' => 0,
            'format' => 'json',
            'module' => 'API',
        ]);

        $response = json_decode($request->process(), true);

        $contains = false;

        foreach ($response as $segment) {
            if ($segment['segment'] === (new VisitorId())->getSegmentName()) {
                $contains = true;
                break;
            }
        }

        $this->assertTrue($contains);
    }

    public function testItDoesNotContainVisitidIfProfileDisabled()
    {
        Cache::flushAll();

        $systemSettings = new SystemSettings();
        $systemSettings->disableVisitorProfile->setValue(1);
        $systemSettings->save();

        $request = new Request([
            'method' => 'API.getSegmentsMetadata',
            'filter_limit' => -1,
            '_hideImplementationData' => 0,
            'format' => 'json',
            'module' => 'API',
        ]);

        $response = json_decode($request->process(), true);

        $contains = false;

        foreach ($response as $segment) {
            if ($segment['segment'] === (new VisitorId())->getSegmentName()) {
                $contains = true;
                break;
            }
        }

        $this->assertFalse($contains);
    }
}
