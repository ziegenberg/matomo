<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Resolution;

use Matomo\Matomo;

function getConfigurationLabel($str)
{
    if (strpos($str, ';') === false) {
        return $str;
    }
    $values = explode(";", $str);

    $os = \Matomo\Plugins\DevicesDetection\getOsFullName($values[0]);
    $name = $values[1];
    $browser = \Matomo\Plugins\DevicesDetection\getBrowserName($name);
    if ($browser === false) {
        $browser = Matomo::translate('General_Unknown');
    }
    $resolution = $values[2];
    return $os . " / " . $browser . " / " . $resolution;
}
