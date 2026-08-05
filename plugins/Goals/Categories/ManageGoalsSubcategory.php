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

class ManageGoalsSubcategory extends Subcategory
{
    protected $categoryId = 'Goals_Goals';
    protected $id = 'Goals_ManageGoals';
    protected $order = 800;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Goals_ManageGoalsSubcategoryHelp1') . '</p>'
            . '<p>' . Url::getExternalLinkTag('https://matomo.org/docs/tracking-goals-web-analytics/') . Matomo::translate('Goals_ManageGoalsSubcategoryHelp2') . '</a></p>';
    }
}
