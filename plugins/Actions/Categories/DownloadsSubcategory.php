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

class DownloadsSubcategory extends Subcategory
{
    protected $categoryId = 'General_Actions';
    protected $id = 'General_Downloads';
    protected $order = 35;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Actions_DownloadsSubcategoryHelp1') . '</p>'
            . '<p>' . Matomo::translate('Actions_DownloadsSubcategoryHelp2') . '</p>';
    }
}
