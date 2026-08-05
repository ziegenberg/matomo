<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Categories;

use Matomo\Category\Category;
use Matomo\Matomo;

class ActionsCategory extends Category
{
    protected $id = 'General_Actions';
    protected $order = 10;
    protected $icon = 'icon-reporting-actions';

    public function getDisplayName()
    {
        return Matomo::translate('Actions_Behaviour');
    }
}
