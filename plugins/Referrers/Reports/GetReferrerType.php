<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Referrers\Reports;

use Matomo\Common;
use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\HtmlTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Matomo\Plugins\CoreVisualizations\Visualizations\Sparklines;
use Matomo\Plugins\Referrers\Columns\ReferrerType;
use Matomo\Widget\WidgetsList;
use Matomo\Report\ReportWidgetFactory;

class GetReferrerType extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new ReferrerType();
        $this->name          = Matomo::translate('Referrers_Type');
        $this->documentation = Matomo::translate('Referrers_TypeReportDocumentation') . '<br />'
                             . '<b>' . Matomo::translate('Referrers_DirectEntry') . ':</b> ' . Matomo::translate('Referrers_DirectEntryDocumentation') . '<br />'
                             . '<b>' . Matomo::translate('Referrers_SearchEngines') . ':</b> ' . Matomo::translate(
                                 'Referrers_SearchEnginesDocumentation',
                                 array('<br />', '&quot;' . Matomo::translate('Referrers_SubmenuSearchEngines') . '&quot;')
                             ) . '<br />'
                             . '<b>' . Matomo::translate('Referrers_Websites') . ':</b> ' . Matomo::translate(
                                 'Referrers_WebsitesDocumentation',
                                 array('<br />', '&quot;' . Matomo::translate('Referrers_SubmenuWebsitesOnly') . '&quot;')
                             ) . '<br />'
                             . '<b>' . Matomo::translate('Referrers_Campaigns') . ':</b> ' . Matomo::translate(
                                 'Referrers_CampaignsDocumentation',
                                 array('<br />', '&quot;' . Matomo::translate('Referrers_Campaigns') . '&quot;')
                             );
        $this->constantRowsCount = true;
        $this->hasGoalMetrics = true;
        $this->order = 1;
        $this->subcategoryId = 'Referrers_WidgetGetAll';
        $this->supportsFlatten = false;
    }

    public function getDefaultTypeViewDataTable()
    {
        return HtmlTable\AllColumns::ID;
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        $widgetsList->addWidgetConfig(
            $factory->createWidget()
                    ->setName('Referrers_ReferrerTypes')
                    ->setSubcategoryId('Referrers_WidgetGetAll')
        );

        $widgetsList->addWidgetConfig(
            $factory->createWidget()
                ->setName('General_EvolutionOverPeriod')
                ->setSubcategoryId('General_Overview')
                ->setAction('getEvolutionGraph')
                ->setOrder(9)
                ->setIsNotWidgetizable()
                ->forceViewDataTable(Evolution::ID)
                ->addParameters(array(
                    'columns' => $defaultColumns = array('nb_visits'),
                ))
        );

        $widgetsList->addWidgetConfig(
            $factory->createCustomWidget('getSparklines')
                ->forceViewDataTable(Sparklines::ID)
                ->setIsNotWidgetizable()
                ->setName('Referrers_Type')
                ->setSubcategoryId('General_Overview')
                ->setOrder(10)
        );
    }

    public function configureView(ViewDataTable $view)
    {
        $idSubtable       = Common::getRequestVar('idSubtable', false);
        $labelColumnTitle = $this->name;

        switch ($idSubtable) {
            case Common::REFERRER_TYPE_SEARCH_ENGINE:
                $labelColumnTitle = Matomo::translate('General_ColumnKeyword');
                break;
            case Common::REFERRER_TYPE_SOCIAL_NETWORK:
                $labelColumnTitle = Matomo::translate('Referrers_ColumnSocial');
                break;
            case Common::REFERRER_TYPE_AI_ASSISTANT:
                $labelColumnTitle = Matomo::translate('Referrers_ColumnAIAssistant');
                break;
            case Common::REFERRER_TYPE_WEBSITE:
                $labelColumnTitle = Matomo::translate('Referrers_ColumnWebsite');
                break;
            case Common::REFERRER_TYPE_CAMPAIGN:
                $labelColumnTitle = Matomo::translate('Referrers_ColumnCampaign');
                break;
            default:
                break;
        }

        $view->config->show_search = false;
        $view->config->show_offset_information = false;
        $view->config->show_pagination_control = false;
        $view->config->show_exclude_low_population = false;
        $view->config->addTranslation('label', $labelColumnTitle);

        $view->requestConfig->filter_limit = 10;

        if ($view->isViewDataTableId(HtmlTable::ID)) {
            $view->config->disable_subtable_when_show_goals = true;
        }

        $this->configureFooterMessage($view);
    }

    protected function configureFooterMessage(ViewDataTable $view)
    {
        if ($this->isSubtableReport) {
            // no footer message for subtables
            return;
        }

        $out = '';
        Matomo::postEvent('Template.afterReferrerTypeReport', array(&$out));
        $view->config->show_footer_message = $out;
    }
}
