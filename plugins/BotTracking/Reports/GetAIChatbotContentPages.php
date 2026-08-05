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
use Matomo\Plugins\BotTracking\Columns\PageUrl;

class GetAIChatbotContentPages extends AbstractAIChatbotContentUrlReport
{
    protected function init(): void
    {
        parent::init();

        $this->name          = Matomo::translate('BotTracking_AIChatbotsContentPagesTitle');
        $this->documentation = Matomo::translate('BotTracking_AIChatbotsContentPagesDocumentation');
        $this->dimension     = new PageUrl();
        $this->order         = 10;
    }

    protected function getRequestsDocumentationKey(): string
    {
        // Scope the "Requests" tooltip to page URLs since this report covers only page actions.
        return 'BotTracking_ColumnPageRequestsDocumentation';
    }
}
