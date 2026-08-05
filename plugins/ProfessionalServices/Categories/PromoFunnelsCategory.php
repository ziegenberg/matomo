<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\ProfessionalServices\Categories;

use Matomo\Category\Category;

class PromoFunnelsCategory extends Category
{
    protected $id = 'ProfessionalServices_PromoFunnels';
    protected $order = 28;
    protected $icon = 'icon-funnel';
}
