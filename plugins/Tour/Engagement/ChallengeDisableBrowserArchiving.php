<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Tour\Engagement;

use Matomo\ArchiveProcessor\Rules;
use Matomo\Matomo;
use Matomo\Url;

class ChallengeDisableBrowserArchiving extends Challenge
{
    public function getName()
    {
        return Matomo::translate('Tour_DisableBrowserArchiving');
    }

    public function getId()
    {
        return 'disable_browser_archiving';
    }

    public function isCompleted(string $login)
    {
        return !Rules::isBrowserTriggerEnabled();
    }

    public function getUrl()
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/docs/setup-auto-archiving/');
    }
}
