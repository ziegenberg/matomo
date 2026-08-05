<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Tour\Engagement;

use Matomo\Container\StaticContainer;
use Matomo\Matomo;
use Matomo\Plugins\Diagnostics\Diagnostic\DiagnosticResult;
use Matomo\Plugins\UserCountry\Diagnostic\GeolocationDiagnostic;
use Matomo\Url;

class ChallengeConfigureGeolocation extends Challenge
{
    /**
     * @var null|bool
     */
    private $completed = null;

    public function getName()
    {
        return Matomo::translate('Tour_ConfigureGeolocation');
    }

    public function getDescription()
    {
        return Matomo::translate('Tour_ConfigureGeolocationDescription');
    }

    public function getId()
    {
        return 'configure_geolocation';
    }

    public function isCompleted(string $login)
    {
        if (!isset($this->completed)) {
            $locationDiagnostic = StaticContainer::get(GeolocationDiagnostic::class);
            $result = $locationDiagnostic->execute();
            $this->completed = !empty($result[0]) && $result[0]->getStatus() === DiagnosticResult::STATUS_OK;
        }
        return $this->completed;
    }

    public function getUrl()
    {
        return 'index.php' . Url::getCurrentQueryStringWithParametersModified(array('module' => 'UserCountry', 'action' => 'adminIndex', 'widget' => false));
    }
}
