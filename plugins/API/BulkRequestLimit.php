<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\API;

use Matomo\Config\GeneralConfig;
use Matomo\Matomo;

class BulkRequestLimit
{
    public static function getCurrentLimit(): int
    {
        $configLimit = GeneralConfig::getConfigValue('API_bulk_request_limit');
        $configLimit = is_numeric($configLimit) ? (int)$configLimit : -1;

        if (Matomo::isUserIsAnonymous()) {
            $defaultLimit = Matomo::isUserHasSomeViewAccess() ? 50 : 10;
            if ($configLimit > -1) {
                return min($defaultLimit, $configLimit);
            }
            return $defaultLimit;
        }

        return $configLimit;
    }
}
