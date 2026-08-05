<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Fixtures;

use Matomo\Container\StaticContainer;
use Matomo\Plugins\GeoIp2\LocationProvider\GeoIp2\Php;
use Matomo\Plugins\PrivacyManager\IPAnonymizer;
use Matomo\Plugins\UserCountry\LocationProvider;
use Matomo\Tests\Framework\Fixture;

class JSTrackingUIFixture extends Fixture
{
    public function setUp(): void
    {
        parent::setUp();

        self::resetPluginsInstalledConfig();
        self::updateDatabase();
        self::installAndActivatePlugins($this->getTestEnvironment());
        self::updateDatabase();

        $trackerUpdater = StaticContainer::get('Matomo\Plugins\CustomJsTracker\TrackerUpdater');
        $trackerUpdater->update();

        // for proper geolocation
        LocationProvider::setCurrentProvider(Php::ID);
        IPAnonymizer::deactivate();

        Fixture::createWebsite('2012-02-02 00:00:00');
    }

    public function performSetUp($setupEnvironmentOnly = false)
    {
        $this->extraTestEnvVars = array(
            'loadRealTranslations' => 1,
        );
        $this->extraPluginsToLoad = array(
            'CustomJsTracker',
            'ExampleTracker',
        );

        parent::performSetUp($setupEnvironmentOnly);

        $this->testEnvironment->overlayUrl = UITestFixture::getLocalTestSiteUrl();
        UITestFixture::createOverlayTestSite($idSite = 1);

        $this->testEnvironment->tokenAuth = self::getTokenAuth();
        $this->testEnvironment->pluginsToLoad = $this->extraPluginsToLoad;
        $this->testEnvironment->save();
    }
}
