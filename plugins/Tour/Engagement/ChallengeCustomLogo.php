<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Tour\Engagement;

use Matomo\Matomo;
use Matomo\Plugins\CoreAdminHome\CustomLogo;
use Matomo\Plugins\Tour\Dao\DataFinder;
use Matomo\Url;

class ChallengeCustomLogo extends Challenge
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
        return Matomo::translate('Tour_UploadLogo');
    }

    public function getDescription()
    {
        return Matomo::translate('CoreAdminHome_CustomLogoHelpText');
    }

    public function getId()
    {
        return 'custom_logo';
    }

    public function isCompleted(string $login)
    {
        if (!isset($this->completed)) {
            $logo = new CustomLogo();
            $this->completed = $logo->isEnabled();
        }
        return $this->completed;
    }

    public function getUrl()
    {
        return 'index.php' . Url::getCurrentQueryStringWithParametersModified(array('module' => 'CoreAdminHome', 'action' => 'generalSettings', 'widget' => false)) . '#/#useCustomLogo';
    }
}
