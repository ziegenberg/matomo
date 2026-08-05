<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Feedback;

use Matomo\Url;
use Matomo\View;
use Matomo\Version;
use Matomo\Container\StaticContainer;

class Controller extends \Matomo\Plugin\Controller
{
    public function index()
    {
        $view = new View('@Feedback/index');
        $this->setGeneralVariablesView($view);
        $popularHelpTopics = StaticContainer::get('popularHelpTopics');
        foreach ($popularHelpTopics as $helpTopic) {
            if (isset($helpTopic['url'])) {
                $helpTopic['url'] = Url::addCampaignParametersToMatomoLink($helpTopic['url']);
            }
        }
        $view->popularHelpTopics = $popularHelpTopics;
        $view->piwikVersion = Version::VERSION;
        return $view->render();
    }
}
