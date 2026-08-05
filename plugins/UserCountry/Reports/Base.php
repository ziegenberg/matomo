<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserCountry\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\UserCountry\UserCountry;
use Matomo\Url;

abstract class Base extends \Matomo\Plugin\Report
{
    protected function init()
    {
        $this->categoryId = 'General_Visitors';
        $this->subcategoryId = 'UserCountry_SubmenuLocations';
        $this->hasGoalMetrics = true;
    }

    protected function getGeoIPReportDocSuffix()
    {
        return Matomo::translate(
            'UserCountry_GeoIPDocumentationSuffix',
            [
                Url::getExternalLinkTag('http://www.maxmind.com/?rId=piwik'),
                '</a>',
                Url::getExternalLinkTag('http://www.maxmind.com/en/city_accuracy?rId=piwik'),
                '</a>',
            ]
        );
    }

    /**
     * Checks if a datatable for a view is empty and if so, displays a message in the footer
     * telling users to configure GeoIP.
     */
    protected function checkIfNoDataForGeoIpReport(ViewDataTable $view)
    {
        $view->config->filters[] = function ($dataTable) use ($view) {
            // if there's only one row whose label is 'Unknown', display a message saying there's no data
            if (
                $dataTable->getRowsCount() == 1
                && $dataTable->getFirstRow()->getColumn('label') == Matomo::translate('General_Unknown')
            ) {
                $footerMessage = Matomo::translate('UserCountry_NoDataForGeoIPReport1');

                $userCountry = new UserCountry();
                // if GeoIP is working, don't display this part of the message
                if (!$userCountry->isGeoIPWorking()) {
                    $params = ['module' => 'UserCountry', 'action' => 'adminIndex'];
                    $footerMessage .= ' ' . Matomo::translate(
                        'UserCountry_NoDataForGeoIPReport2',
                        [
                            '<a target="_blank" href="' . Url::getCurrentQueryStringWithParametersModified($params) . '">',
                            '</a>',
                            Url::getExternalLinkTag('https://db-ip.com/?refid=mtm'),
                            '</a>',
                        ]
                    );
                } else {
                    $footerMessage .= ' ' . Matomo::translate(
                        'UserCountry_ToGeolocateOldVisits',
                        [Url::getExternalLinkTag('https://matomo.org/faq/how-to/faq_167'), '</a>']
                    );
                }

                $view->config->show_footer_message = $footerMessage;
            }
        };
    }
}
