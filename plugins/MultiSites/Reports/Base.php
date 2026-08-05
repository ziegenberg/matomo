<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\MultiSites\Reports;

use Matomo\Columns\Dimension;
use Matomo\Matomo;
use Matomo\Plugins\MultiSites\API;

abstract class Base extends \Matomo\Plugin\Report
{
    protected function init()
    {
        $this->categoryId = 'General_MultiSitesSummary';

        $allMetricsInfo = API::getApiMetrics($enhanced = true);

        $metadataMetrics = [];
        $processedMetricsMetadata = [];

        foreach ($allMetricsInfo as $metricName => $metricSettings) {
            $metadataMetrics[$metricName] =
                Matomo::translate($metricSettings[API::METRIC_TRANSLATION_KEY]);

            $processedMetricsMetadata[$metricSettings[API::METRIC_EVOLUTION_COL_NAME_KEY]] =
                Matomo::translate($metricSettings[API::METRIC_TRANSLATION_KEY]) . " " . Matomo::translate('MultiSites_Evolution');
        }

        $this->metrics = array_keys($metadataMetrics);
        $this->processedMetrics = array_keys($processedMetricsMetadata);
    }

    public function getMetricSemanticTypes(): array
    {
        $metricTypes                         = parent::getMetricSemanticTypes();
        $metricTypes['ai_chatbots_requests'] = Dimension::TYPE_NUMBER;

        return $metricTypes;
    }
}
