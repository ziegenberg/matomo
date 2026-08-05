<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\AIAgents\Providers;

use Matomo\Tracker\Request;

class NovaAct extends AgentAbstract
{
    public function getId(): string
    {
        return 'NovaAct';
    }

    public function isDetectedForTrackerRequest(Request $trackerRequest): bool
    {
        $userAgent = $trackerRequest->getUserAgent();

        return stripos($userAgent, ' Agent-NovaAct/') !== false;
    }
}
