<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserId\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;
use Matomo\Url;

class VisitorsUserSubcategory extends Subcategory
{
    protected $categoryId = 'General_Visitors';
    protected $id = 'UserId_UserReportTitle';
    protected $order = 40;


    public function getHelp()
    {
        return '<p>' . Matomo::translate('UserId_VisitorsUserSubcategoryHelp') . '</p>'
            . '<p>' . Url::getExternalLinkTag('https://matomo.org/docs/user-id', null, null, 'App.UserId.getUsers')
            . '<span class="icon-info"></span> ' . Matomo::translate('CoreAdminHome_LearnMore') . '</a></p>';
    }
}
