<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\VisitTime\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\Graph;
use Matomo\Plugins\VisitTime\Columns\LocalTime;
use Matomo\Plugin\ReportsProvider;

class GetVisitInformationPerLocalTime extends Base
{
    protected $defaultSortColumn = '';

    protected function init()
    {
        parent::init();
        $this->dimension     = new LocalTime();
        $this->name          = Matomo::translate('VisitTime_LocalTime');
        $this->documentation = Matomo::translate('VisitTime_WidgetLocalTimeDocumentation', array('<strong>', '</strong>'));
        $this->constantRowsCount = true;
        $this->order = 15;

        $this->subcategoryId = 'VisitTime_SubmenuTimes';
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_flatten_table = false;
        $view->config->show_flatten_table_export = false;
        $this->setBasicConfigViewProperties($view);

        $view->requestConfig->filter_limit = 24;

        $view->config->title = Matomo::translate('VisitTime_ColumnLocalTime');
        $view->config->addTranslation('label', Matomo::translate('VisitTime_LocalTime'));

        if ($view->isViewDataTableId(Graph::ID)) {
            $view->config->max_graph_elements = false;
        }
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('VisitTime', 'getByDayOfWeek'),
        );
    }
}
