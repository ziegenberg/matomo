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
use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugin\AggregatedMetric;
use Matomo\Plugins\BotTracking\Metrics;

class ServerError5xxRequests extends AggregatedMetric
{
    public function getName()
    {
        return Metrics::COLUMN_SERVER_ERROR_5XX_REQUESTS;
    }

    public function getTranslatedName()
    {
        return Matomo::translate('BotTracking_ColumnServerError5xxRequests');
    }

    public function getDocumentation()
    {
        return Matomo::translate('BotTracking_ColumnServerError5xxRequestsDocumentation');
    }

    /**
     * @param int $value
     * @return string
     */
    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyNumber($value);
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_NUMBER;
    }
}
