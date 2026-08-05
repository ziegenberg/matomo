<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserLanguage\Reports;

use Matomo\Matomo;
use Matomo\Plugins\UserLanguage\Columns\Language;
use Matomo\Plugin\ReportsProvider;

class GetLanguageCode extends GetLanguage
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new Language();
        $this->name          = Matomo::translate('UserLanguage_LanguageCode');
        $this->documentation = Matomo::translate('UserLanguage_getLanguageCodeDocumentation');
        $this->order = 11;
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('UserLanguage', 'getLanguage'),
        );
    }
}
