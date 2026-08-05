<?php

return array(
    'observers.global' => Matomo\DI::add(array(
        array('Request.dispatchCoreAndPluginUpdatesScreen', \Matomo\DI::value(function () {
            $pluginName = 'TagManager';
            $unloadTagManager = \Matomo\Container\StaticContainer::get('test.vars.unloadTagManager');
            $tagManagerTeaser = new \Matomo\Plugins\CorePluginsAdmin\Model\TagManagerTeaser(\Matomo\Matomo::getCurrentUserLogin());
            if ($unloadTagManager) {
                $pluginManager = \Matomo\Plugin\Manager::getInstance();
                if (
                    $pluginManager->isPluginActivated($pluginName)
                    && $pluginManager->isPluginLoaded($pluginName)
                ) {
                    $pluginManager->unloadPlugin($pluginName);
                }
                $tagManagerTeaser->reset();
            }
        })),
    )),
);
