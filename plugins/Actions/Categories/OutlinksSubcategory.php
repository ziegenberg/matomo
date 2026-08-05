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

class OutlinksSubcategory extends Subcategory
{
    protected $categoryId = 'General_Actions';
    protected $id = 'General_Outlinks';
    protected $order = 30;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Actions_OutlinksSubcategoryHelp1') . '</p>'
            . '<p>' . Matomo::translate('Actions_PagesSubcategoryHelp3') . '</p>';
    }
}
