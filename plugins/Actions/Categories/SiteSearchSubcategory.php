<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Actions\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;
use Matomo\Url;

class SiteSearchSubcategory extends Subcategory
{
    protected $categoryId = 'General_Actions';
    protected $id = 'Actions_SubmenuSitesearch';
    protected $order = 25;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Actions_SiteSearchSubcategoryHelp1') . '</p>'
            . '<p>' . Matomo::translate('Actions_SiteSearchSubcategoryHelp2') . '</p>'
            . '<p>' . Url::getExternalLinkTag('https://matomo.org/docs/site-search/', null, null, 'App.Actions.getSiteSearchCategories')
            . Matomo::translate('Actions_SiteSearchSubcategoryHelp3') . '</a></p>';
    }
}
