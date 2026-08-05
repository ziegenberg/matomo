<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Categories;

use Matomo\Category\Category;
use Matomo\Matomo;

class VisitorsCategory extends Category
{
    protected $id = 'General_Visitors';
    protected $order = 5;
    protected $icon = 'icon-reporting-visitors';

    public function getHelp()
    {
        $visitsLogUrl = '<a href="#" onclick="this.href=broadcast.buildReportingUrl(\'category=General_Visitors&subcategory=Live_VisitorLog\')">';

        $helpText = '<p>' . Matomo::translate('CoreHome_VisitorsCategoryHelp1') . '</p>';
        $helpText .= '<p>' . Matomo::translate('CoreHome_VisitorsCategoryHelp2', [$visitsLogUrl, '</a>']) . '</p>';

        return $helpText;
    }
}
