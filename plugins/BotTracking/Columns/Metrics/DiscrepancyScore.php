<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\BotTracking\Columns\Metrics;

use InvalidArgumentException;
use Matomo\Columns\Dimension;
use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugin\AggregatedMetric;
use Matomo\Plugins\BotTracking\Metrics;

/**
 * Column metadata (name / translation / per-variant documentation / formatting) for the bounded
 * 0–100 Discrepancy Score on the Human/AI-Favoured Pages reports. The value is materialised during
 * archiving by {@see \Matomo\Plugins\BotTracking\RecordBuilders\AIChatbotFavouredPages} (via
 * {@see \Matomo\Plugins\BotTracking\DataTable\FavouredPagesScorer}). AggregatedMetric is the closest
 * base for "a plain column that isn't a processed metric" (as for the two source-metric columns).
 */
class DiscrepancyScore extends AggregatedMetric
{
    public const VARIANT_HUMAN_FAVOURED = 'human_favoured';
    public const VARIANT_AI_FAVOURED    = 'ai_favoured';

    /** @var self::VARIANT_HUMAN_FAVOURED|self::VARIANT_AI_FAVOURED */
    private $variant;

    public function __construct(string $variant)
    {
        if ($variant !== self::VARIANT_HUMAN_FAVOURED && $variant !== self::VARIANT_AI_FAVOURED) {
            throw new InvalidArgumentException(
                'Unknown DiscrepancyScore variant: ' . $variant
            );
        }
        $this->variant = $variant;
    }

    public function getName()
    {
        return Metrics::COLUMN_DISCREPANCY_SCORE;
    }

    public function getTranslatedName()
    {
        return Matomo::translate('BotTracking_ColumnDiscrepancyScore');
    }

    public function getDocumentation()
    {
        $key = $this->variant === self::VARIANT_HUMAN_FAVOURED
            ? 'BotTracking_ColumnDiscrepancyScoreHumanFavouredDocumentation'
            : 'BotTracking_ColumnDiscrepancyScoreAIFavouredDocumentation';

        return Matomo::translate($key);
    }

    /**
     * @param float|int $value
     * @return string
     */
    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyNumber((float) $value, 1);
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_NUMBER;
    }
}
