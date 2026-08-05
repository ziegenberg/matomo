<?php

namespace Matomo\Plugins\CoreHome\Widgets;

use Matomo\Matomo;
use Matomo\Plugins\SitesManager\SitesManager;
use Matomo\Plugins\UsersManager\UsersManager;
use Matomo\Widget\Widget;
use Matomo\Widget\WidgetConfig;

class QuickLinks extends Widget
{
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('About Matomo');
        $config->setName('CoreHome_QuickLinks');
        $config->setOrder(16);
        $config->setIsEnabled(Matomo::hasUserSuperUserAccess());
    }

    public function render()
    {
        $hasUsersAdmin = UsersManager::isUsersAdminEnabled();
        $hasSitesAdmin = SitesManager::isSitesAdminEnabled();

        return $this->renderTemplate('quickLinks', array(
            'hasUsersAdmin' => $hasUsersAdmin,
            'hasSitesAdmin' => $hasSitesAdmin,
        ));
    }
}
