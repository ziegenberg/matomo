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

class ChallengeAddedSegment extends Challenge
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
        return Matomo::translate('Tour_AddSegment');
    }

    public function getDescription()
    {
        return Matomo::translate('SegmentEditor_PluginDescription');
    }

    public function getId()
    {
        return 'add_segment';
    }

    public function isCompleted(string $login)
    {
        if (!isset($this->completed)) {
            $this->completed = $this->finder->hasAddedSegment($login);
        }
        return $this->completed;
    }

    public function getUrl()
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/docs/segmentation/');
    }
}
