<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tracker;

use Matomo\Config\SectionConfig;
use Matomo\Tracker\Config\ThirdPartyCookies;

class TrackerConfig extends SectionConfig
{
    public static function getSectionName(): string
    {
        return 'Tracker';
    }

    /**
     * @return mixed|null
     */
    protected static function getRawConfigValue(string $name, ?int $idSite = null)
    {
        if ($name === 'use_third_party_id_cookie') {
            return ThirdPartyCookies::getInstance($idSite)->getValue();
        }

        return parent::getRawConfigValue($name, $idSite);
    }
}
