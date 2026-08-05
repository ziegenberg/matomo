<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Tour\Engagement;

use Matomo\Matomo;
use Matomo\Plugins\Tour\Dao\DataFinder;
use Matomo\Url;

class ChallengeTrackingCode extends Challenge
{
    private DataFinder $finder;

    /**
     * @var null|bool
     */
    private $completed = null;

    public function __construct(DataFinder $dataFinder)
    {
        $this->finder = $dataFinder;
    }

    public function getName()
    {
        return Matomo::translate('Tour_EmbedTrackingCode');
    }

    public function getDescription()
    {
        return Matomo::translate('CoreAdminHome_TrackingCodeIntro');
    }

    public function getId()
    {
        return 'track_data';
    }

    public function isCompleted(string $login)
    {
        if (!isset($this->completed)) {
            $this->completed = $this->finder->hasTrackedData();
        }
        return $this->completed;
    }

    public function getUrl()
    {
        return 'index.php' . Url::getCurrentQueryStringWithParametersModified(array('module' => 'CoreAdminHome', 'action' => 'trackingCodeGenerator', 'widget' => false));
    }
}
