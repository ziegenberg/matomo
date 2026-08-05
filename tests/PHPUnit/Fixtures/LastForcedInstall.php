<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Fixtures;

use Matomo\Container\StaticContainer;
use Matomo\Plugins\CorePluginsAdmin\PluginInstaller;

class LastForcedInstall extends LatestStableInstall
{
    public const FORCED_VERSION = "5.11.2";

    public function setUp(): void
    {
        parent::setUp();

        $this->installAndActivateTreemapPlugin();
    }

    protected function getDownloadUrl()
    {
        return 'http://builds.matomo.org/matomo-' . self::FORCED_VERSION . '.zip';
    }

    private function installAndActivateTreemapPlugin()
    {
        // creating a new instance here as otherwise we would change the "global" environment,
        // but we only want to change Matomo version temporarily for this task here
        $environment = StaticContainer::getContainer()->make('Matomo\Plugins\Marketplace\Environment');
        $environment->setPiwikVersion(self::FORCED_VERSION);
        /** @var \Matomo\Plugins\Marketplace\Api\Client $marketplaceClient */
        $marketplaceClient = StaticContainer::getContainer()->make('Matomo\Plugins\Marketplace\Api\Client', [
            'environment' => $environment,
        ]);

        $oldGlobal = $GLOBALS['MATOMO_PLUGIN_COPY_DIR'] ?? false;
        $GLOBALS['MATOMO_PLUGIN_COPY_DIR'] = $this->getInstallSubdirectoryPath() . '/plugins/';

        $pluginInstaller = new PluginInstaller($marketplaceClient);
        $pluginInstaller->installOrUpdatePluginFromMarketplace('TreemapVisualization');

        if ($oldGlobal) {
            $GLOBALS['MATOMO_PLUGIN_COPY_DIR'] = $oldGlobal;
        } else {
            unset($GLOBALS['MATOMO_PLUGIN_COPY_DIR']);
        }

        passthru($this->getInstallSubdirectoryPath() . '/console plugin:activate TreemapVisualization');
    }
}
