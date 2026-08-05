<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\VisitTime;

class Archiver extends \Matomo\Plugin\Archiver
{
    public const SERVER_TIME_RECORD_NAME = 'VisitTime_serverTime';
    public const LOCAL_TIME_RECORD_NAME = 'VisitTime_localTime';
}
