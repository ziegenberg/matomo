<?php

namespace Matomo\Plugins\testpluginUpdates;

use Matomo\Updates as PiwikUpdates;

class Updates_0_5 extends PiwikUpdates
{
    function doUpdate(\Matomo\Updater $updater)
    {
        throw new \Matomo\Exception\MissingFilePermissionException('make sure this exception is thrown');
    }
}
