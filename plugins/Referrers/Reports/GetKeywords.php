<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Referrers\Reports;

use Matomo\EventDispatcher;
use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\HtmlTable;
use Matomo\Plugins\Referrers\Columns\Keyword;

class GetKeywords extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new Keyword();
        $this->name          = Matomo::translate('Referrers_Keywords');
        $this->documentation = Matomo::translate('Referrers_KeywordsReportDocumentation', '<br /><br />') .
                               '<br /><br />' . Matomo::translate('Referrers_KeywordsReportDocumentationNote');
        $this->actionToLoadSubTables = 'getSearchEnginesFromKeywordId';
        $this->hasGoalMetrics = true;
        $this->order = 3;
        $this->subcategoryId = 'Referrers_SubmenuSearchEngines';
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_exclude_low_population = false;
        $view->config->addTranslation('label', Matomo::translate('General_ColumnKeyword'));

        $view->requestConfig->filter_limit = 25;

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
        EventDispatcher::getInstance()->postEvent('Template.afterReferrersKeywordsReport', array(&$out));
        $view->config->show_footer_message = $out;
    }
}
