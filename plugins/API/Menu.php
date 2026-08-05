<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\API;

use Matomo\Container\StaticContainer;
use Matomo\Menu\MenuAdmin;
use Matomo\Menu\MenuTop;
use Matomo\Matomo;
use DeviceDetector\Parser\OperatingSystem;
use Matomo\Url;

class Menu extends \Matomo\Plugin\Menu
{
    public const DD_SHORT_NAME_ANDROID = 'AND';
    public const DD_SHORT_NAME_IOS     = 'IOS';

    public function configureTopMenu(MenuTop $menu)
    {
        $this->addTopMenuMobileApp($menu);
    }

    public function configureAdminMenu(MenuAdmin $menu)
    {
        $menu->addPlatformItem(
            'General_API',
            $this->urlForAction('listAllAPI', array('segment' => false)),
            7,
            Matomo::translate('API_TopLinkTooltip')
        );

        if (Matomo::isUserIsAnonymous()) {
            $menu->addPlatformItem(
                'API_Glossary',
                $this->urlForAction('glossary', array('segment' => false)),
                50
            );
        }
    }

    private function addTopMenuMobileApp(MenuTop $menu)
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return;
        }

        if (!class_exists("DeviceDetector\\DeviceDetector")) {
            throw new \Exception("DeviceDetector could not be found, maybe you are using Piwik from git and need to update Composer. Execute this command: php composer.phar update");
        }

        $ua = new OperatingSystem($_SERVER['HTTP_USER_AGENT']);
        $ua->setCache(StaticContainer::get('DeviceDetector\Cache\Cache'));
        $parsedOS = $ua->parse();

        if (!empty($parsedOS['short_name']) && in_array($parsedOS['short_name'], array(self::DD_SHORT_NAME_ANDROID, self::DD_SHORT_NAME_IOS))) {
            $menu->addItem('Mobile_MatomoMobile', null, Url::addCampaignParametersToMatomoLink('https://matomo.org/mobile/'), 4);
        }
    }
}
