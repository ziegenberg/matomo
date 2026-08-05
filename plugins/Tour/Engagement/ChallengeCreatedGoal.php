<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Tour\Engagement;

use Matomo\Matomo;
use Matomo\Url;

class ChallengeCreatedGoal extends Challenge
{
    public function getName()
    {
        return Matomo::translate('Tour_DefineGoal');
    }

    public function getDescription()
    {
        return Matomo::translate('Tour_DefineGoalDescription');
    }

    public function getId()
    {
        return 'define_goal';
    }

    public function getUrl()
    {
        return 'index.php' . Url::getCurrentQueryStringWithParametersModified(array('module' => 'Goals', 'action' => 'manage', 'widget' => false));
    }
}
