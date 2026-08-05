<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\VisitFrequency;

use Matomo\Common;
use Matomo\FrontController;
use Matomo\Matomo;
use Matomo\Plugins\CoreVisualizations\Visualizations\Sparklines;
use Matomo\SettingsPiwik;
use Matomo\Translation\Translator;

class Controller extends \Matomo\Plugin\Controller
{
    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;

        parent::__construct();
    }

    /**
     * @deprecated used to be a widgetized URL. There to not break widget URLs
     */
    public function getSparklines()
    {
        $_GET['forceView'] = '1';
        $_GET['viewDataTable'] = Sparklines::ID;

        return FrontController::getInstance()->fetchDispatch('VisitFrequency', 'get');
    }

    public function getEvolutionGraph()
    {
        $columns = Common::getRequestVar('columns', false);
        if (false !== $columns) {
            $columns = Matomo::getArrayFromApiParameter($columns);
        }

        $documentation = $this->translator->translate('VisitFrequency_ReturningVisitsDocumentation') . '<br />'
            . $this->translator->translate('General_BrokenDownReportDocumentation') . '<br />'
            . $this->translator->translate('VisitFrequency_ReturningVisitDocumentation');

        $period = Common::getRequestVar('period', false);

        $columnNames = array('nb_visits');
        if (SettingsPiwik::isUniqueVisitorsEnabled($period)) {
            $columnNames[] = 'nb_uniq_visitors';
        }
        $columnNames[] = 'nb_actions';
        $columnNames[] = 'nb_actions_per_visit';
        $columnNames[] = 'bounce_rate';
        $columnNames[] = 'avg_time_on_site';

        $suffixes = array('_returning', '_new', '');

        $selectableColumns = array();
        foreach ($suffixes as $suffix) {
            foreach ($columnNames as $column) {
                $selectableColumns[] = $column . $suffix;
            }
        }

        $view = $this->getLastUnitGraphAcrossPlugins(
            $this->pluginName,
            __FUNCTION__,
            $columns,
            $selectableColumns,
            $documentation
        );

        if (empty($view->config->columns_to_display)) {
            $view->config->columns_to_display = array('nb_visits_returning');
        }

        return $this->renderView($view);
    }
}
