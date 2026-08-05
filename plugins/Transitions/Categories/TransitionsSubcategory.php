<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Transitions\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;
use Matomo\Url;

class TransitionsSubcategory extends Subcategory
{
    protected $categoryId = 'General_Actions';
    protected $id = 'Transitions_Transitions';
    protected $order = 46;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Transitions_TransitionsSubcategoryHelp1') . '</p>'
            . '<p>' . Url::getExternalLinkTag('https://matomo.org/docs/transitions/', null, null, 'App.Transitions.getTransitions')
            . Matomo::translate('Transitions_TransitionsSubcategoryHelp2') . '</a></p>';
    }
}
