<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\ProfessionalServices\Widgets;

use Matomo\Widget\WidgetConfig;

class Rss extends \Matomo\Widget\Widget
{
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('About Matomo');
        $config->setIsEnabled(false);
    }

    public function render()
    {
        return '';
    }
}
