<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Columns\Metrics;

use Matomo\Columns\Dimension;
use Matomo\DataTable\Row;
use Matomo\Matomo;
use Matomo\Plugin\ProcessedMetric;

/**
 * The average number of actions per visit. Calculated as:
 *
 *     nb_actions / nb_visits
 *
 * nb_actions & nb_visits are calculated during archiving.
 */
class ActionsPerVisit extends ProcessedMetric
{
    public function getName()
    {
        return 'nb_actions_per_visit';
    }

    public function compute(Row $row)
    {
        $actions = $this->getMetric($row, 'nb_actions');
        $visits = $this->getMetric($row, 'nb_visits');

        return Matomo::getQuotientSafe($actions, $visits, $precision = 1);
    }

    public function getTranslatedName()
    {
        return Matomo::translate('General_ColumnActionsPerVisit');
    }

    public function getDependentMetrics()
    {
        return array('nb_actions', 'nb_visits');
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_NUMBER;
    }
}
