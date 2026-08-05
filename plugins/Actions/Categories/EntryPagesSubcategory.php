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

class EntryPagesSubcategory extends Subcategory
{
    protected $categoryId = 'General_Actions';
    protected $id = 'Actions_SubmenuPagesEntry';
    protected $order = 10;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Actions_EntryPagesSubcategoryHelp1') . '</p>'
            . '<p>' . Matomo::translate('Actions_EntryPagesSubcategoryHelp2') . '</p>'
            . '<p>' . Matomo::translate('Actions_PagesSubcategoryHelp3') . '</p>';
    }
}
