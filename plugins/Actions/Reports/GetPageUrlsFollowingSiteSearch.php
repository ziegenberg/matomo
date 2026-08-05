<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Actions\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\Actions\Columns\DestinationPage;
use Matomo\Plugin\ReportsProvider;

class GetPageUrlsFollowingSiteSearch extends GetPageTitlesFollowingSiteSearch
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new DestinationPage();
        $this->name          = Matomo::translate('Actions_WidgetPageUrlsFollowingSearch');
        $this->documentation = Matomo::translate('Actions_SiteSearchFollowingPagesDoc') . '<br/>' . Matomo::translate('General_UsePlusMinusIconsDocumentation');
        $this->order = 16;

        $this->subcategoryId = 'Actions_SubmenuSitesearch';
    }

    public function configureView(ViewDataTable $view)
    {
        $title = Matomo::translate('Actions_WidgetPageTitlesFollowingSearch');

        $this->configureViewForUrlAndTitle($view, $title);
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('Actions', 'getPageTitlesFollowingSiteSearch'),
        );
    }
}
