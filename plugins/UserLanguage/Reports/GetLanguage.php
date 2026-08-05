<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserLanguage\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\UserLanguage\Columns\Language;
use Matomo\Plugin\ReportsProvider;

class GetLanguage extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new Language();
        $this->name          = Matomo::translate('UserLanguage_BrowserLanguage');
        $this->documentation = Matomo::translate('UserLanguage_getLanguageDocumentation');
        $this->order = 8;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_flatten_table = false;
        $view->config->show_flatten_table_export = false;
        $view->config->show_search = false;
        $view->config->columns_to_display = array('label', 'nb_visits');
        $view->config->show_exclude_low_population = false;

        $view->requestConfig->filter_sort_column = 'nb_visits';
        $view->requestConfig->filter_sort_order  = 'desc';
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('UserLanguage', 'getLanguageCode'),
        );
    }
}
