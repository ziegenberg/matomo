<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\BotTracking\Reports;

use Matomo\Matomo;

class GetAIChatbotsRealTime extends AbstractAIChatbotsRealTimeChatbotsReport
{
    protected function init(): void
    {
        parent::init();

        $this->name          = Matomo::translate('BotTracking_AIChatbotsLast30MinutesTitle');
        $this->documentation = Matomo::translate('BotTracking_AIChatbotsLast30MinutesDocumentation');
        $this->order         = 10;
    }

    /**
     * @return array<int, array{name: string, documentation: string, lastMinutes: int, order: int}>
     */
    protected function getWidgetDefinitions(): array
    {
        return AIChatbotsRealTimeWidgets::getChatbotWidgets();
    }
}
