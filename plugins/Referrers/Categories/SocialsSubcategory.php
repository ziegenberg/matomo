<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Referrers\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;

class SocialsSubcategory extends Subcategory
{
    protected $categoryId = 'Referrers_Referrers';
    protected $id = 'Referrers_Socials';
    protected $order = 16;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Referrers_SocialsSubcategoryHelp') . '</p>'
            . '<p>' . Matomo::translate('Referrers_WebsitesSubcategoryHelp2') . '</p>';
    }
}
