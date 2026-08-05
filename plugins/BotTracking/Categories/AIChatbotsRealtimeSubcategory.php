<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\BotTracking\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;

class AIChatbotsRealtimeSubcategory extends Subcategory
{
    protected $categoryId = 'General_AIAssistants';
    protected $id = 'BotTracking_AIChatbotsRealtime';
    protected $order = 12;

    public function getHelp()
    {
        return sprintf(
            '<p>%1$s</p><p>%2$s</p>',
            Matomo::translate('BotTracking_AIChatbotsRealtimeHelp1'),
            Matomo::translate('BotTracking_AIChatbotsRealtimeHelp2')
        );
    }
}
