<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\RssWidget;

use Matomo\Plugins\RssWidget\Widgets\RssChangelog;
use Matomo\Plugins\RssWidget\Widgets\RssPiwik;
use Matomo\SettingsPiwik;
use Matomo\Widget\WidgetsList;

class RssWidget extends \Matomo\Plugin
{
    /**
     * @see \Matomo\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
            'Request.getRenamedModuleAndAction' => 'renameExampleRssWidgetModule',
            'Widget.filterWidgets' => 'filterWidgets',
        );
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/RssWidget/stylesheets/rss.less";
    }

    public function renameExampleRssWidgetModule(&$module, &$action)
    {
        if ($module == 'ExampleRssWidget') {
            $module = 'RssWidget';
        }
    }

    /**
     * @param WidgetsList $list
     */
    public function filterWidgets($list)
    {
        if (!SettingsPiwik::isInternetEnabled()) {
            $list->remove(RssChangelog::getCategory(), RssChangelog::getName());
            $list->remove(RssPiwik::getCategory(), RssPiwik::getName());
        }
    }
}
