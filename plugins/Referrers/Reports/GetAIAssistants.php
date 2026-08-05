<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\Referrers\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\HtmlTable;
use Matomo\Plugins\Goals\Visualizations\Goals;
use Matomo\Plugins\Referrers\Columns\AIAssistant;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Request;
use Matomo\Widget\WidgetsList;

class GetAIAssistants extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension = new AIAssistant();
        $this->name = Matomo::translate('Referrers_AIAssistants');
        $this->documentation = Matomo::translate('Referrers_AIAssistantsReportDocumentation', '<br />');
        $this->hasGoalMetrics = true;
        $this->order = 13;
        $this->subcategoryId = 'Referrers_AIAssistants';

        if (Request::fromRequest()->getStringParameter('secondaryDimension', '') === 'entryPageTitle') {
            $this->actionToLoadSubTables = 'getEntryPageTitlesForAIAssistant';
        } else {
            $this->actionToLoadSubTables = 'getEntryPageUrlsForAIAssistant';
        }
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        $widget = $factory->createWidget()->setName('Referrers_AIAssistants');
        $widgetsList->addWidgetConfig($widget);
    }

    public function getDefaultTypeViewDataTable()
    {
        return HtmlTable\AllColumns::ID;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_pivot_by_subtable = false;
        $view->config->show_exclude_low_population = false;

        $view->requestConfig->filter_limit = 10;

        if ($view->isViewDataTableId(HtmlTable::ID)) {
            $view->config->disable_subtable_when_show_goals = true;

            if (!$view->isViewDataTableId(Goals::ID)) {
                $secondaryDimensions = [
                    'entryPageUrl'   => Matomo::translate('Actions_ColumnEntryPageURL'),
                    'entryPageTitle' => Matomo::translate('Actions_ColumnEntryPageTitle'),
                ];
                $view->config->setSecondaryDimensions($secondaryDimensions, 'entryPageUrl');
            }
        }
    }
}
