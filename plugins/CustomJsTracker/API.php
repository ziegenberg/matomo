<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CustomJsTracker;

use Matomo\Container\StaticContainer;
use Matomo\Matomo;
use Matomo\Plugins\CustomJsTracker\Exception\AccessDeniedException;

/**
 * Provides API methods for custom JavaScript tracker configuration.
 *
 * @method static \Matomo\Plugins\CustomJsTracker\API getInstance()
 */
class API extends \Matomo\Plugin\API
{
    /**
     * Returns whether plugin tracker files will be included automatically in `matomo.js`.
     *
     * @return bool Whether plugin tracker files are included automatically.
     */
    public function doesIncludePluginTrackersAutomatically(): bool
    {
        Matomo::checkUserHasSomeAdminAccess();

        try {
            $updater = StaticContainer::get('Matomo\Plugins\CustomJsTracker\TrackerUpdater');
            $updater->checkWillSucceed();
            return true;
        } catch (AccessDeniedException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
