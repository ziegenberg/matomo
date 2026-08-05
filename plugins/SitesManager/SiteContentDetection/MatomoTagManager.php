<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\SitesManager\SiteContentDetection;

use Matomo\Matomo;
use Matomo\SiteContentDetector;
use Matomo\Url;

class MatomoTagManager extends SiteContentDetectionAbstract
{
    public static function getName(): string
    {
        return Matomo::translate('SitesManager_SiteWithoutDataMatomoTagManager');
    }

    public static function getIcon(): string
    {
        return './plugins/SitesManager/images/mtm.svg';
    }

    public static function getContentType(): int
    {
        return self::TYPE_TRACKER;
    }

    public static function getPriority(): int
    {
        return 10;
    }

    public function isDetected(?string $data = null, ?array $headers = null): bool
    {
        $tests = ['/matomo ?tag ?manager/i', '/_mtm\.push/'];
        foreach ($tests as $test) {
            if (preg_match($test, $data) === 1) {
                return true;
            }
        }

        return false;
    }

    public function renderInstructionsTab(SiteContentDetector $detector): string
    {
        return '<h3>' . Matomo::translate('SitesManager_SiteWithoutDataMatomoTagManager') . '</h3>
            <p>' . Matomo::translate(
            'SitesManager_SiteWithoutDataMatomoTagManagerNotActive',
            [Url::getExternalLinkTag('https://matomo.org/docs/tag-manager/'), '</a>']
        ) . '</p>';
    }
}
