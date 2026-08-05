<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\VisitFrequency;

use Matomo\Plugins\VisitFrequency\API as VisitFrequencyAPI;

class Archiver extends \Matomo\Plugin\Archiver
{
    public function getDependentSegmentsToArchive(): array
    {
        return [
            ['plugin' => 'VisitsSummary', 'segment' => VisitFrequencyAPI::NEW_VISITOR_SEGMENT],
            ['plugin' => 'VisitsSummary', 'segment' => VisitFrequencyAPI::RETURNING_VISITOR_SEGMENT],
        ];
    }
}
