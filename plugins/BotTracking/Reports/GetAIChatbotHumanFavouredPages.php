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
use Matomo\Plugins\BotTracking\Columns\Metrics\DiscrepancyScore;

class GetAIChatbotHumanFavouredPages extends AbstractAIChatbotFavouredPagesReport
{
    protected function init(): void
    {
        parent::init();

        $this->name          = Matomo::translate('BotTracking_AIChatbotsHumanFavouredPagesTitle');
        $this->documentation = Matomo::translate('BotTracking_AIChatbotsHumanFavouredPagesDocumentation');
        // Order 40 keeps this the second-to-last widget so it pairs with AI-Favoured (order 50)
        // into the side-by-side row — see the layout contract in
        // AbstractAIChatbotFavouredPagesReport::configureWidgets().
        $this->order         = 40;
    }

    protected function getDiscrepancyScoreVariant(): string
    {
        return DiscrepancyScore::VARIANT_HUMAN_FAVOURED;
    }
}
