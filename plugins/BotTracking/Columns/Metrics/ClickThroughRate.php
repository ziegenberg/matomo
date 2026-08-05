<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\BotTracking\Columns\Metrics;

use Matomo\Columns\Dimension;
use Matomo\DataTable\Row;
use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugin\ProcessedMetric;
use Matomo\Plugins\BotTracking\Metrics;

class ClickThroughRate extends ProcessedMetric
{
    public function getName()
    {
        return Metrics::METRIC_AI_CHATBOTS_CLICK_THROUGH_RATE;
    }

    public function getTranslatedName()
    {
        return Matomo::translate('BotTracking_ColumnClickThroughRate');
    }

    public function getDocumentation()
    {
        return Matomo::translate('BotTracking_ColumnClickThroughRateDocumentation');
    }

    public function getDependentMetrics()
    {
        return [
            Metrics::METRIC_AI_CHATBOTS_REQUESTS,
            Metrics::METRIC_AI_CHATBOTS_ACQUIRED_VISITS,
        ];
    }

    public function compute(Row $row)
    {
        $rawRequests = $this->getMetric($row, Metrics::METRIC_AI_CHATBOTS_REQUESTS);
        $rawVisits  = $this->getMetric($row, Metrics::METRIC_AI_CHATBOTS_ACQUIRED_VISITS);

        $requests = is_numeric($rawRequests) ? (int) $rawRequests : 0;
        $visits  = is_numeric($rawVisits)  ? (int) $rawVisits  : 0;

        return Matomo::getQuotientSafe($visits, $requests, 4);
    }

    /**
     * @param number $value
     * @return string
     */
    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyPercentFromQuotient($value);
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_PERCENT;
    }
}
