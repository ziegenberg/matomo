<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;
use Matomo\Url;

class GoalsOverviewSubcategory extends Subcategory
{
    protected $categoryId = 'Goals_Goals';
    protected $id = 'General_Overview';
    protected $order = 2;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Goals_GoalsOverviewSubcategoryHelp1') . '</p>'
            . '<p>' . Matomo::translate('Goals_GoalsOverviewSubcategoryHelp2') . '</p>'
            . '<p>' . Url::getExternalLinkTag('https://matomo.org/docs/tracking-goals-web-analytics/', null, null, 'App.Goals.Overview')
            . Matomo::translate('Goals_ManageGoalsSubcategoryHelp2') . '</a></p>';
    }
}
