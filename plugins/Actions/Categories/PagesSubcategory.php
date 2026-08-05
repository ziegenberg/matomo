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

class PagesSubcategory extends Subcategory
{
    protected $categoryId = 'General_Actions';
    protected $id = 'General_Pages';
    protected $order = 5;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Actions_PagesSubcategoryHelp1') . '</p>'
            . '<p>' . Matomo::translate('Actions_PagesSubcategoryHelp2') . '</p>'
            . '<p>' . Matomo::translate('Actions_PagesSubcategoryHelp3') . '</p>';
    }
}
