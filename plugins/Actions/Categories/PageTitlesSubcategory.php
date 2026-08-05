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

class PageTitlesSubcategory extends Subcategory
{
    protected $categoryId = 'General_Actions';
    protected $id = 'Actions_SubmenuPageTitles';
    protected $order = 20;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Actions_PageTitlesSubcategoryHelp1') . '</p>'
            . '<p>' . Matomo::translate('Actions_PageTitlesSubcategoryHelp2') . '</p>';
    }
}
