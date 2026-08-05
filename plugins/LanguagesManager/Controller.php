<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 *
 */

namespace Matomo\Plugins\LanguagesManager;

use Matomo\Common;
use Matomo\Nonce;
use Matomo\Matomo;
use Matomo\Url;

class Controller extends \Matomo\Plugin\ControllerAdmin
{
    /**
     * anonymous = in the session
     * authenticated user = in the session
     */
    public function saveLanguage()
    {
        $language = Common::getRequestVar('language');
        $nonce = Common::getRequestVar('nonce', '');

        Nonce::checkNonce(LanguagesManager::LANGUAGE_SELECTION_NONCE, $nonce);

        LanguagesManager::setLanguageForSession($language);
        Url::redirectToReferrer();
    }

    public function searchTranslation()
    {
        Matomo::checkUserHasSomeAdminAccess();

        return $this->renderTemplate('searchTranslation');
    }
}
