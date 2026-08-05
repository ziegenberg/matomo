<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Contents\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;
use Matomo\Url;

class ContentsSubcategory extends Subcategory
{
    protected $categoryId = 'General_Actions';
    protected $id = 'Contents_Contents';
    protected $order = 45;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Contents_ContentsSubcategoryHelp1') . '</p>'
            . '<p>' . Url::getExternalLinkTag('https://matomo.org/docs/content-tracking', null, null, 'App.Contents.getContentNames')
            . Matomo::translate('Contents_ContentsSubcategoryHelp2') . '</a></p>';
    }
}
