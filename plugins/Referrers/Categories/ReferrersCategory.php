<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Referrers\Categories;

use Matomo\Category\Category;
use Matomo\Matomo;

class ReferrersCategory extends Category
{
    protected $id = 'Referrers_Referrers';
    protected $order = 15;
    protected $icon = 'icon-reporting-referer';

    public function getDisplayName()
    {
        return Matomo::translate('Referrers_Acquisition');
    }
}
