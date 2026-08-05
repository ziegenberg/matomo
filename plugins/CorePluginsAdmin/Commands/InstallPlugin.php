<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CorePluginsAdmin\Commands;

use Matomo\Config as PiwikConfig;
use Matomo\Container\StaticContainer;
use Matomo\Plugin\ConsoleCommand;
use Matomo\Plugin\Manager;
use Matomo\Plugins\CorePluginsAdmin\PluginInstaller;
use Matomo\Plugins\Marketplace\Marketplace;

/**
 * plugin:install console command.
 */
class InstallPlugin extends ConsoleCommand
{
    protected function configure(): void
    {
        $this->setName('plugin:install-or-update');
        $this->setDescription('Install or update a plugin.');
        $this->addOptionalArgument('plugin', 'The name of the plugin you want to install or update. Multiple plugin names can be specified separated by a space.', null, true);
    }

    protected function doExecute(): int
    {
        PiwikConfig::getInstance()->checkConfigIsWritable();

        $input = $this->getInput();
        $output = $this->getOutput();
        $pluginManager = Manager::getInstance();

        if (!Marketplace::isMarketplaceEnabled()) {
            $output->writeln(sprintf("<error>Marketplace is not enabled, can't install or update plugins.</error>"));
            return self::FAILURE;
        }

        $pluginNames = $input->getArgument('plugin');

        foreach ($pluginNames as $pluginName) {
            try {
                $this->installPlugin($pluginName);
                $output->writeln(sprintf("Installed or updated plugin <info>%s</info>", $pluginName));
            } catch (\Matomo\Plugins\CorePluginsAdmin\PluginInstallerException $e) {
                $output->writeln(sprintf("<error>Unable to install or update plugin %s. %s</error>", $pluginName, $e->getMessage()));
                continue;
            }
        }

        return self::SUCCESS;
    }

    private function installPlugin(string $pluginName): void
    {
        $pluginInstaller = StaticContainer::get(PluginInstaller::class);
        $pluginInstaller->installOrUpdatePluginFromMarketplace($pluginName);

        $pluginManager = Manager::getInstance();
        $pluginManager->loadPlugin($pluginName);
        $pluginManager->installLoadedPlugins();
    }
}
