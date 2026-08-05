<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Contents\Reports;

use Matomo\Matomo;
use Matomo\Plugins\Contents\Columns\ContentName;
use Matomo\Plugins\Contents\Columns\Metrics\InteractionRate;

/**
 * This class defines a new report.
 *
 * See {@link https://developer.matomo.org/api-reference/Piwik/Plugin/Report} for more information.
 */
class GetContentNames extends Base
{
    protected function init()
    {
        parent::init();

        $this->name          = Matomo::translate('Contents_ContentName');
        $this->documentation = Matomo::translate('Contents_ContentNameReportDocumentation');
        $this->dimension     = new ContentName();
        $this->order         = 35;
        $this->actionToLoadSubTables = 'getContentNames';

        $this->metrics = array('nb_impressions', 'nb_interactions');
        $this->processedMetrics = array(new InteractionRate());
    }
}
