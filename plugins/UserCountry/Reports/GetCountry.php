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
use Matomo\Plugins\UserCountry\Columns\Country;
use Matomo\Plugins\UserCountry\LocationProvider;
use Matomo\Url;

class GetCountry extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension      = new Country();
        $this->name           = Matomo::translate('UserCountry_Country');
        $this->documentation  = Matomo::translate('UserCountry_getCountryDocumentation');
        $this->order = 5;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_flatten_table = false;
        $view->config->show_flatten_table_export = false;
        $view->config->show_exclude_low_population = false;
        $view->config->documentation = $this->documentation;

        $view->requestConfig->filter_limit = 5;

        if (LocationProvider::getCurrentProviderId() == LocationProvider\DefaultProvider::ID) {
            // if we're using the default location provider, add a note explaining how it works
            $footerMessage = Matomo::translate("General_Note") . ': '
                . Matomo::translate(
                    'UserCountry_DefaultLocationProviderExplanation',
                    [Url::getExternalLinkTag('https://matomo.org/docs/geo-locate/'), '</a>']
                );

            $view->config->show_footer_message = $footerMessage;
        }
    }
}
