<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals\Reports;

use Matomo\Matomo;
use Matomo\Plugins\CoreHome\Columns\Metrics\ConversionRate;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;

class GetMetrics extends Get
{
    protected function init()
    {
        parent::init();

        $this->name = Matomo::translate('Goals_Goals');
        $this->processedMetrics = array(new ConversionRate());
        $this->documentation = ''; // TODO
        $this->order = 1;
        $this->orderGoal = 50;
        $this->metrics = array( 'nb_conversions', 'nb_visits_converted', 'revenue');
        $this->parameters = null;
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
    }

    public function configureReportMetadata(&$availableReports, $infos)
    {
    }
}
