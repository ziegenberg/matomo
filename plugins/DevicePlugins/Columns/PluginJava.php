<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\DevicePlugins\Columns;

use Matomo\Common;
use Matomo\Matomo;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visitor;
use Matomo\Tracker\Action;

class PluginJava extends DevicePluginColumn
{
    protected $columnName = 'config_java';
    protected $columnType = 'TINYINT(1) NULL';
    protected $type = self::TYPE_BOOL;

    public function getName()
    {
        return Matomo::translate('General_Plugin') . ' (Java)';
    }

    /**
     * @param Action|null $action
     * @return mixed
     */
    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        return Common::getRequestVar('java', 0, 'int', $request->getParams());
    }
}
