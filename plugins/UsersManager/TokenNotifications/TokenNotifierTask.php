<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UsersManager\TokenNotifications;

use Exception;
use Matomo\Container\StaticContainer;
use Matomo\Date;
use Matomo\Log\LoggerInterface;
use Matomo\Option;
use Matomo\Plugin\Manager as PluginManager;
use Matomo\Scheduler\Schedule\Daily;
use Matomo\Scheduler\Task;

/**
 * Send token notifications for each provider
 */
class TokenNotifierTask extends Task
{
    public const LAST_RUN_TIME_OPTION_NAME = 'TokenNotifier.lastRunTime';

    public function __construct()
    {
        parent::__construct($this, 'dispatchNotifications', null, new Daily());
    }

    /**
     * Get a list of providers (class names) that may provide token notifications to be dispatched
     *
     * @return array
     */
    private function getTokenProviderClasses(): array
    {
        return PluginManager::getInstance()->findMultipleComponents(
            'TokenNotifications',
            TokenNotificationProviderInterface::class
        );
    }

    /**
     * Dispatch notifications for each provider and its tokens
     */
    public function dispatchNotifications()
    {
        $container = StaticContainer::getContainer();
        $logger = $container->get(LoggerInterface::class);

        try {
            Option::set(self::LAST_RUN_TIME_OPTION_NAME, Date::factory('today')->getTimestamp());

            $notificationsToDispatchCount = 0;
            $notificationsDispatchedCount = 0;

            foreach ($this->getTokenProviderClasses() as $providerClass) {
                /** @var TokenNotificationProviderInterface $provider */
                $provider = $container->get($providerClass);

                $providerTokenNotificationsForDispatch = $provider->getTokenNotificationsForDispatch();
                $notificationsToDispatchCount += count($providerTokenNotificationsForDispatch);

                foreach ($providerTokenNotificationsForDispatch as $tokenNotification) {
                    $dispatched = $tokenNotification->dispatch();
                    if ($dispatched) {
                        foreach ($tokenNotification->getTokenIds() as $tokenId) {
                            $provider->setTokenNotificationDispatched($tokenId);
                        }
                        $notificationsDispatchedCount++;
                    }
                }
            }

            if ($notificationsToDispatchCount) {
                $logger->info(
                    "Number of token notifications dispatched: {number} of {total}.",
                    ['number' => $notificationsDispatchedCount, 'total' => $notificationsToDispatchCount]
                );
            } else {
                $logger->info("No token notifications to dispatch, task rescheduled.");
            }
        } catch (Exception $ex) {
            $container->get(LoggerInterface::class)->error($ex);
            throw $ex;
        }
    }
}
