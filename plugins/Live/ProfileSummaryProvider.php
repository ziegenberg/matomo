<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Live;

use Matomo\Cache;
use Matomo\CacheId;
use Matomo\Plugin;
use Matomo\Matomo;
use Matomo\Plugins\Live\ProfileSummary\ProfileSummaryAbstract;

class ProfileSummaryProvider
{
    private Plugin\Manager $pluginManager;

    public function __construct(Plugin\Manager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    /**
     * Returns all available profile summaries
     *
     * @return ProfileSummaryAbstract[]
     * @throws \Exception
     */
    public function getAllInstances()
    {
        $cacheId = CacheId::pluginAware('ProfileSummaries');
        $cache   = Cache::getTransientCache();

        if (!$cache->contains($cacheId)) {
            $instances = [];

            /**
             * Triggered to add new live profile summaries.
             *
             * **Example**
             *
             *     public function addProfileSummary(&$profileSummaries)
             *     {
             *         $profileSummaries[] = new MyCustomProfileSummary();
             *     }
             *
             * @param ProfileSummaryAbstract[] $profileSummaries An array of profile summaries
             */
            Matomo::postEvent('Live.addProfileSummaries', array(&$instances));

            foreach ($this->getAllProfileSummaryClasses() as $className) {
                $instances[] = new $className();
            }

            /**
             * Triggered to filter / restrict profile summaries.
             *
             * **Example**
             *
             *     public function filterProfileSummary(&$profileSummaries)
             *     {
             *         foreach ($profileSummaries as $index => $profileSummary) {
             *              if ($profileSummary->getId() === 'myid') {}
             *                  unset($profileSummaries[$index]); // remove all summaries having this ID
             *              }
             *         }
             *     }
             *
             * @param ProfileSummaryAbstract[] $profileSummaries An array of profile summaries
             */
            Matomo::postEvent('Live.filterProfileSummaries', array(&$instances));

            $cache->save($cacheId, $instances);
        }

        return $cache->fetch($cacheId);
    }

    /**
     * Returns class names of all VisitorDetails classes.
     *
     * @return string[]
     * @api
     */
    protected function getAllProfileSummaryClasses()
    {
        return $this->pluginManager->findMultipleComponents('ProfileSummary', 'Matomo\Plugins\Live\ProfileSummary\ProfileSummaryAbstract');
    }
}
