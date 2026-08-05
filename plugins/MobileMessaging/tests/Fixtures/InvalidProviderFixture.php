<?php

namespace Matomo\Plugins\MobileMessaging\tests\Fixtures;

use Matomo\Settings\Storage\Factory;
use Matomo\Tests\Fixtures\EmptySite;

class InvalidProviderFixture extends EmptySite
{
    public function setUp(): void
    {
        parent::setUp();

        (new Factory())->getPluginStorage('MobileMessaging', '')->getBackend()->save([
            'Provider' => 'InValid',
            'APIKey' => [],
        ]);
    }
}
