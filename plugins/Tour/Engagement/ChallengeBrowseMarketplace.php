<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Tour\Engagement;

use Matomo\Matomo;
use Matomo\Url;

class ChallengeBrowseMarketplace extends Challenge
{
    public function getName()
    {
        return Matomo::translate('Tour_BrowseMarketplace');
    }

    public function getDescription()
    {
        return Matomo::translate('Marketplace_PluginDescription');
    }

    public function getId()
    {
        return 'browse_marketplace';
    }

    public function getUrl()
    {
        return 'index.php' . Url::getCurrentQueryStringWithParametersModified(array('module' => 'Marketplace', 'action' => 'overview', 'widget' => false));
    }
}
