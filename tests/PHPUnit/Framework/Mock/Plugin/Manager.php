<?php

namespace Matomo\Tests\Framework\Mock\Plugin;

class Manager extends \Matomo\Plugin\Manager
{
    private $pluginsToActivate = array();

    public function __construct()
    {
    }

    public function setActivatedPlugins($pluginsList)
    {
        $this->pluginsToActivate = $pluginsList;
    }

    public function isPluginActivated($pluginName)
    {
        return in_array($pluginName, $this->pluginsToActivate);
    }
}
