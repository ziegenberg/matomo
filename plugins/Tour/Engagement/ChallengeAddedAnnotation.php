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

class ChallengeAddedAnnotation extends Challenge
{
    public function getName()
    {
        return Matomo::translate('Tour_AddAnnotation');
    }

    public function getDescription()
    {
        return Matomo::translate('Annotations_PluginDescription');
    }

    public function getId()
    {
        return 'add_annotation';
    }

    public function getUrl()
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/docs/annotations/');
    }
}
