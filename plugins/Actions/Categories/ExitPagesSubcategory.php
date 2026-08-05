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

class ExitPagesSubcategory extends Subcategory
{
    protected $categoryId = 'General_Actions';
    protected $id = 'Actions_SubmenuPagesExit';
    protected $order = 15;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Actions_ExitPagesSubcategoryHelp1') . '</p>'
            . '<p>' . Matomo::translate('Actions_ExitPagesSubcategoryHelp2') . '</p>'
            . '<p>' . Matomo::translate('Actions_PagesSubcategoryHelp3') . '</p>';
    }
}
