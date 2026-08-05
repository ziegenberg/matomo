<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\AIAgents\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;

class AIAgentsOverviewSubcategory extends Subcategory
{
    protected $categoryId = 'General_AIAssistants';
    protected $id = 'AIAgents_AIAgentsOverview';
    protected $order = 20;

    public function getHelp()
    {
        return sprintf(
            '<p>%1$s</p>',
            Matomo::translate('AIAgents_AIAgentsOverviewSubcategoryDescription')
        );
    }
}
