<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals;

use Matomo\API\Request;
use Matomo\Common;
use Matomo\DataTable;
use Matomo\Http\JsonResponse;
use Matomo\DataTable\Filter\AddColumnsProcessedMetricsGoal;
use Matomo\FrontController;
use Matomo\Metrics\Formatter;
use Matomo\NumberFormatter;
use Matomo\Matomo;
use Matomo\Plugin\Manager;
use Matomo\Plugins\CoreVisualizations\Visualizations\Sparklines;
use Matomo\Plugins\Live\Live;
use Matomo\Plugins\Referrers\API as APIReferrers;
use Matomo\Site;
use Matomo\Translation\Translator;
use Matomo\View;
use Matomo\ViewDataTable\Factory as ViewDataTableFactory;
use Matomo\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;

class Controller extends \Matomo\Plugin\Controller
{
    /**
     * Number of "Your top converting keywords/etc are" to display in the per Goal overview page
     * @var int
     */
    public const COUNT_TOP_ROWS_TO_DISPLAY = 3;

    protected $goalColumnNameToLabel = array(
        'avg_order_revenue' => 'General_AverageOrderValue',
        'nb_conversions'    => 'Goals_ColumnConversions',
        'conversion_rate'   => 'General_ColumnConversionRate',
        'revenue'           => 'General_TotalRevenue',
        'items'             => 'General_PurchasedProducts',
    );

    private Translator $translator;
    private $goals;

    private function formatConversionRate($conversionRate, $columnName = 'conversion_rate')
    {
        if ($conversionRate instanceof DataTable) {
            if ($conversionRate->getRowsCount() == 0) {
                $conversionRate = 0;
            } else {
                $conversionRate = $conversionRate->getFirstRow()->getColumn($columnName);
            }
        }

        return $conversionRate;
    }

    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->translator = $translator;

        if (!empty($this->idSite)) {
            $this->goals = Request::processRequest('Goals.getGoals', ['idSite' => $this->idSite, 'filter_limit' => '-1', 'orderByName' => true], $default = []);
        } else {
            $this->goals = [];
        }
    }

    public function manage()
    {
        Matomo::checkUserHasWriteAccess($this->idSite);

        $view = new View('@Goals/manageGoals');
        $this->setGeneralVariablesView($view);
        $this->setEditGoalsViewVariables($view);
        $this->setGoalOptions($view);
        $this->execAndSetResultsForTwigEvents($view);
        return $view->render();
    }

    public function goalConversionsOverview()
    {
        $view = new View('@Goals/conversionOverview');
        $idGoal = Common::getRequestVar('idGoal', null, 'string');

        $view->topDimensions = $this->getTopDimensions($idGoal);

        $goalMetrics = Request::processRequest('Goals.get', array('idGoal' => $idGoal));

        // conversion rate for new and returning visitors
        $view->conversion_rate_returning = $this->formatConversionRate($goalMetrics, 'conversion_rate_returning_visit');
        $view->conversion_rate_new = $this->formatConversionRate($goalMetrics, 'conversion_rate_new_visit');
        $view->idGoal = $idGoal;
        $view->visitorLogEnabled = Manager::getInstance()->isPluginActivated('Live') && Live::isVisitorLogEnabled($this->idSite);

        return $view->render();
    }

    public function getLastNbConversionsGraph()
    {
        $view = $this->getLastUnitGraph($this->pluginName, __FUNCTION__, 'Goals.getConversions');
        return $this->renderView($view);
    }

    public function getLastConversionRateGraph()
    {
        $view = $this->getLastUnitGraph($this->pluginName, __FUNCTION__, 'Goals.getConversionRate');
        return $this->renderView($view);
    }

    public function getLastRevenueGraph()
    {
        $view = $this->getLastUnitGraph($this->pluginName, __FUNCTION__, 'Goals.getRevenue');
        return $this->renderView($view);
    }

    public function addNewGoal()
    {
        $view = new View('@Goals/addNewGoal');
        $this->setGeneralVariablesView($view);
        $this->setGoalOptions($view);
        $view->onlyShowAddNewGoal = true;
        $view->ecommerceEnabled = $this->site->isEcommerceEnabled();
        $this->execAndSetResultsForTwigEvents($view);
        return $view->render();
    }

    public function editGoals()
    {
        $view = new View('@Goals/editGoals');
        $this->setGeneralVariablesView($view);
        $this->setEditGoalsViewVariables($view);
        $this->setGoalOptions($view);
        $this->execAndSetResultsForTwigEvents($view);
        return $view->render();
    }

    private function execAndSetResultsForTwigEvents(View $view)
    {
        if (empty($view->onlyShowAddGoal)) {
            $beforeGoalListActionsBody = [];

            if ($view->goals) {
                foreach ($view->goals as $goal) {
                    $str = '';
                    Matomo::postEvent('Template.beforeGoalListActionsBody', [&$str, $goal]);

                    $beforeGoalListActionsBody[$goal['idgoal']] = $str;
                }
            }

            $view->beforeGoalListActionsBodyEventResult = $beforeGoalListActionsBody;

            $str = '';
            Matomo::postEvent('Template.beforeGoalListActionsHead', [&$str]);
            $view->beforeGoalListActionsHead = $str;
        }

        if (!empty($view->userCanEditGoals)) {
            $str = '';
            Matomo::postEvent('Template.endGoalEditTable', [&$str]);

            $view->endEditTable = $str;
        }
    }

    #[JsonResponse]
    public function hasConversions(): string
    {
        $this->checkSitePermission();

        $idGoal = Common::getRequestVar('idGoal', '', 'string');
        $period = Common::getRequestVar('period', null, 'string');
        $date   = Common::getRequestVar('date', null, 'string');

        Matomo::checkUserHasViewAccess($this->idSite);

        $conversions = new Conversions();

        $numConversions = $conversions->getConversionForGoal($idGoal, $this->idSite, $period, $date);

        return json_encode($numConversions > 0);
    }

    public function getEvolutionGraph(array $columns = array(), $idGoal = false, array $defaultColumns = array())
    {
        if (empty($columns)) {
            $columns = Common::getRequestVar('columns', false);
            if (false !== $columns) {
                $columns = Matomo::getArrayFromApiParameter($columns);
            }
        }

        if (false !== $columns) {
            $columns = !is_array($columns) ? array($columns) : $columns;
        }

        if (empty($idGoal)) {
            $idGoal = Common::getRequestVar('idGoal', '', 'string');
        }
        $view = $this->getLastUnitGraph($this->pluginName, __FUNCTION__, 'Goals.get');
        $view->requestConfig->request_parameters_to_modify['idGoal'] = $idGoal;
        $view->requestConfig->request_parameters_to_modify['showAllGoalSpecificMetrics'] = 1;

        $nameToLabel = $this->goalColumnNameToLabel;
        if ($idGoal == Matomo::LABEL_ID_GOAL_IS_ECOMMERCE_ORDER) {
            $nameToLabel['nb_conversions'] = 'General_EcommerceOrders';
        } elseif ($idGoal == Matomo::LABEL_ID_GOAL_IS_ECOMMERCE_CART) {
            $nameToLabel['nb_conversions'] = 'General_AbandonedCarts';
            $nameToLabel['conversion_rate'] = $this->translator->translate('Goals_ConversionRate', $this->translator->translate('Goals_AbandonedCart'));
            $nameToLabel['revenue'] = $this->translator->translate('Goals_LeftInCart', $this->translator->translate('General_ColumnRevenue'));
            $nameToLabel['items'] = $this->translator->translate('Goals_LeftInCart', $this->translator->translate('Goals_Products'));
        }

        $selectableColumns = array('nb_conversions', 'conversion_rate', 'revenue');
        $goalSelectableColumns = $selectableColumns;
        if ($this->site->isEcommerceEnabled()) {
            $selectableColumns[] = 'items';
            $selectableColumns[] = 'avg_order_revenue';
        }

        foreach (array_merge($columns ? $columns : array(), $selectableColumns) as $columnName) {
            $columnTranslation = $this->getColumnTranslation($nameToLabel, $columnName, $idGoal);
            $view->config->addTranslation($columnName, $columnTranslation);
        }

        if ($idGoal === '') {
            foreach ($this->goals as $aGoal) {
                foreach ($goalSelectableColumns as $goalColumn) {
                    $goalMetricName = Goals::makeGoalColumn($aGoal['idgoal'], $goalColumn);
                    $selectableColumns[] = $goalMetricName;
                    $columnTranslation = $this->getColumnTranslation($nameToLabel, $goalColumn, $aGoal['idgoal']);
                    $view->config->addTranslation($goalMetricName, $columnTranslation);
                }
            }
        }

        if (!empty($columns)) {
            $view->config->columns_to_display = $columns;
        } elseif (empty($view->config->columns_to_display) && !empty($defaultColumns)) {
            $view->config->columns_to_display = $defaultColumns;
        }

        $view->config->selectable_columns = $selectableColumns;

        $langString = $idGoal ? 'Goals_SingleGoalOverviewDocumentation' : 'Goals_GoalsOverviewDocumentation';
        $view->config->documentation = $this->translator->translate($langString, '<br />');

        if ($view instanceof Evolution) {
            $view->requestConfig->request_parameters_to_modify['format_metrics'] = 0;
        }

        return $this->renderView($view);
    }

    public function getSparklines()
    {
        $content = "";
        $goals = Request::processRequest('Goals.getGoals', ['idSite' => $this->idSite, 'filter_limit' => '-1', 'orderByName' => true], []);

        foreach ($goals as $goal) {
            $params = [
                'idGoal' => $goal['idgoal'],
                'allow_multiple' => (int) $goal['allow_multiple'],
                'only_summary' => 1,
            ];

            \Matomo\Context::executeWithQueryParameters($params, function () use (&$content, $goal) {
                //load Visualisations Sparkline
                $view = ViewDataTableFactory::build(Sparklines::ID, 'Goals.getMetrics', 'Goals.' . __METHOD__, true);
                $view->config->show_title = true;
                $view->config->custom_parameters = [
                    'idGoal' => $goal['idgoal'],
                ];
                $content .= $view->render();
            });
        }

        return $content;
    }

    private function getColumnTranslation($nameToLabel, $columnName, $idGoal)
    {
        $columnTranslation = '';
        // find the right translation for this column, eg. find 'revenue' if column is Goal_1_revenue
        foreach ($nameToLabel as $metric => $metricTranslation) {
            if (strpos($columnName, $metric) !== false) {
                $columnTranslation = $this->translator->translate($metricTranslation);
                break;
            }
        }

        if (
            !empty($idGoal)
            && isset($this->goals[$idGoal])
        ) {
            $goalName = $this->goals[$idGoal]['name'];
            $columnTranslation = "$columnTranslation (" . $this->translator->translate('Goals_GoalX', "$goalName") . ")";
        }

        return $columnTranslation;
    }

    protected function getTopDimensions($idGoal)
    {
        $topDimensionsToLoad = array();

        if (\Matomo\Plugin\Manager::getInstance()->isPluginActivated('UserCountry')) {
            $topDimensionsToLoad += array(
                'country' => 'UserCountry.getCountry',
            );
        }

        $keywordNotDefinedString = '';
        if (\Matomo\Plugin\Manager::getInstance()->isPluginActivated('Referrers')) {
            $keywordNotDefinedString = APIReferrers::getKeywordNotDefinedString();
            $topDimensionsToLoad += array(
                'keyword' => 'Referrers.getKeywords',
                'website' => 'Referrers.getWebsites',
            );
        }

        $topDimensionsToLoad += array(
            'entry_page' => 'Actions.getEntryPageUrls',
        );

        $topDimensions = array();
        foreach ($topDimensionsToLoad as $dimensionName => $apiMethod) {
            if ($apiMethod == 'Actions.getEntryPageUrls') {
                $columnNbConversions = 'goal_' . $idGoal . '_nb_conversions_entry';
                $columnConversionRate = 'goal_' . $idGoal . '_nb_conversions_entry_rate';
                $idGoalToProcess = AddColumnsProcessedMetricsGoal::GOALS_ENTRY_PAGES;
            } else {
                $columnNbConversions = 'goal_' . $idGoal . '_nb_conversions';
                $columnConversionRate = 'goal_' . $idGoal . '_conversion_rate';
                $idGoalToProcess = AddColumnsProcessedMetricsGoal::GOALS_FULL_TABLE;
            }

            $requestParams = [
                'method'                                 => $apiMethod,
                'format'                                 => 'original',
                'format_metrics'                         => 0,
                'filter_update_columns_when_show_all_goals' => 1,
                'idGoal'                                 => $idGoalToProcess,
                'filter_sort_order'                      => 'desc',
                'filter_sort_column'                     => $columnNbConversions,
                'showColumns'                            => "label,$columnNbConversions,$columnConversionRate",
                // select a couple more in case some are not valid (ie. conversions==0 or they are "Keyword not defined")
                'filter_limit'                           => self::COUNT_TOP_ROWS_TO_DISPLAY + 2,
            ];

            if ($apiMethod == 'Actions.getEntryPageUrls') {
                $requestParams['flat'] = 1;
            }

            $request = new Request($requestParams);

            /** @var DataTable $datatable */
            $datatable = $request->process();
            $formatter = new Formatter();
            $topDimension = array();
            $count = 0;

            foreach ($datatable->getRows() as $row) {
                $conversions = $row->getColumn($columnNbConversions);
                if (
                    $conversions > 0
                    && $count < self::COUNT_TOP_ROWS_TO_DISPLAY

                    // Don't put the "Keyword not defined" in the best segment since it's irritating
                    && !($dimensionName == 'keyword'
                        && $row->getColumn('label') == $keywordNotDefinedString)
                ) {
                    $topDimension[] = array(
                        'name'            => $row->getColumn('label'),
                        'nb_conversions'  => $formatter->getPrettyNumber($conversions),
                        'conversion_rate' => $formatter->getPrettyPercentFromQuotient($row->getColumn($columnConversionRate)),
                        'metadata'        => $row->getMetadata(),
                    );
                    $count++;
                }
            }
            $topDimensions[$dimensionName] = $topDimension;
        }
        return $topDimensions;
    }

    protected function getMetricsForGoal($idGoal, $dataRow = null)
    {
        if (!$dataRow) {
            $request = new Request([
                'method' => 'Goals.get',
                'format' => 'original',
                'idGoal' => $idGoal,
            ]);
            /** @var DataTable $datatable */
            $datatable = $request->process();
            $dataRow = $datatable->getFirstRow();
        }
        $nbConversions = $dataRow->getColumn('nb_conversions');
        $nbVisitsConverted = $dataRow->getColumn('nb_visits_converted');
        // Backward compatibility before 1.3, this value was not processed
        if (empty($nbVisitsConverted)) {
            $nbVisitsConverted = $nbConversions;
        }
        $revenue = $dataRow->getColumn('revenue');
        $return = array(
            'id'                         => $idGoal,
            'nb_conversions'             => (int)$nbConversions,
            'nb_visits_converted'        => (int)$nbVisitsConverted,
            'conversion_rate'            => $this->formatConversionRate($dataRow->getColumn('conversion_rate')),
            'revenue'                    => $revenue ? $revenue : 0,
            'urlSparklineConversions'    => $this->getUrlSparkline('getEvolutionGraph', array('columns' => array('nb_conversions'), 'idGoal' => $idGoal)),
            'urlSparklineConversionRate' => $this->getUrlSparkline('getEvolutionGraph', array('columns' => array('conversion_rate'), 'idGoal' => $idGoal)),
            'urlSparklineRevenue'        => $this->getUrlSparkline('getEvolutionGraph', array('columns' => array('revenue'), 'idGoal' => $idGoal)),
        );
        if ($idGoal == Matomo::LABEL_ID_GOAL_IS_ECOMMERCE_ORDER) {
            $items = $dataRow->getColumn('items');
            $aov = $dataRow->getColumn('avg_order_revenue');
            $return = array_merge($return, array(
                                                'revenue_subtotal'              => $dataRow->getColumn('revenue_subtotal'),
                                                'revenue_tax'                   => $dataRow->getColumn('revenue_tax'),
                                                'revenue_shipping'              => $dataRow->getColumn('revenue_shipping'),
                                                'revenue_discount'              => $dataRow->getColumn('revenue_discount'),

                                                'items'                         => $items ? $items : 0,
                                                'avg_order_revenue'             => $aov ? $aov : 0,
                                                'urlSparklinePurchasedProducts' => $this->getUrlSparkline('getEvolutionGraph', array('columns' => array('items'), 'idGoal' => $idGoal)),
                                                'urlSparklineAverageOrderValue' => $this->getUrlSparkline('getEvolutionGraph', array('columns' => array('avg_order_revenue'), 'idGoal' => $idGoal)),
            ));
        }
        return $return;
    }

    private function setEditGoalsViewVariables($view)
    {
        $goals = $this->goals;

        foreach ($goals as &$goal) {
            $goal['revenue_pretty'] = NumberFormatter::getInstance()->formatCurrency($goal['revenue'], Site::getCurrencySymbolFor($this->idSite));
        }

        $view->goals = $goals;

        $idGoal = Common::getRequestVar('idGoal', 0, 'int');
        $view->idGoal = 0;
        if ($idGoal && array_key_exists($idGoal, $goals)) {
            $view->idGoal = $idGoal;
        }

        $view->goalsJSON = json_encode($goals);
        $view->ecommerceEnabled = $this->site->isEcommerceEnabled();
    }

    private function setGoalOptions(View $view)
    {
        $view->userCanEditGoals = Matomo::isUserHasWriteAccess($this->idSite);
        $view->goalTriggerTypeOptions = array(
            'visitors' => Matomo::translate('Goals_WhenVisitors'),
            'manually' => Matomo::translate('Goals_Manually'),
        );
        $view->goalMatchAttributeOptions = array(
            array('key' => 'url', 'value' => Matomo::translate('Goals_VisitUrl')),
            array('key' => 'title', 'value' => Matomo::translate('Goals_VisitPageTitle')),
            array('key' => 'event', 'value' => Matomo::translate('Goals_SendEvent')),
            array('key' => 'file', 'value' => Matomo::translate('Goals_Download')),
            array('key' => 'external_website', 'value' => Matomo::translate('Goals_ClickOutlink')),
            ['key' => 'visit_duration', 'value' => Matomo::translate('Goals_VisitDurationMatchAttr')],
        );
        $view->allowMultipleOptions = array(
            array('key' => '0', 'value' => Matomo::translate('Goals_DefaultGoalConvertedOncePerVisit')),
            array('key' => '1', 'value' => Matomo::translate('Goals_AllowGoalConvertedMoreThanOncePerVisit')),
        );
        $view->eventTypeOptions = array(
            array('key' => 'event_category', 'value' => Matomo::translate('Events_EventCategory')),
            array('key' => 'event_action', 'value' => Matomo::translate('Events_EventAction')),
            array('key' => 'event_name', 'value' => Matomo::translate('Events_EventName')),
        );
        $view->patternTypeOptions = array(
            array('key' => 'contains', 'value' => Matomo::translate('Goals_Contains', '')),
            array('key' => 'exact', 'value' => Matomo::translate('Goals_IsExactly', '')),
            array('key' => 'regex', 'value' => Matomo::translate('Goals_MatchesExpression', '')),
        );
        $view->numericComparisonTypeOptions = [
            ['key' => 'greater_than', 'value' => Matomo::translate('General_OperationGreaterThan')],
        ];
    }

    /**
     * @deprecated used to be a widgetized URL. There to not break widget URLs
     */
    public function widgetGoalReport()
    {
        $idGoal = Common::getRequestVar('idGoal', '', 'string');

        if ($idGoal === Matomo::LABEL_ID_GOAL_IS_ECOMMERCE_ORDER) {
            $_GET['containerId'] = 'EcommerceOverview';
        } elseif (!empty($idGoal)) {
            $_GET['containerId'] = 'Goal_' . (int) $idGoal;
        } else {
            return '';
        }

        return FrontController::getInstance()->fetchDispatch('CoreHome', 'renderWidgetContainer');
    }

    /**
     * @deprecated used to be a widgetized URL. There to not break widget URLs
     */
    public function widgetGoalsOverview()
    {
        $_GET['containerId'] = 'GoalsOverview';

        return FrontController::getInstance()->fetchDispatch('CoreHome', 'renderWidgetContainer');
    }
}
