<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Unit\PHPStan\Rules\data;

use Matomo\Http\JsonResponse;

class JsonResponseInheritanceBaseController extends \Matomo\Plugin\Controller
{
    #[JsonResponse]
    public function jsonAction(): string
    {
        return json_encode([]);
    }

    public function plainAction(): string
    {
        return 'plain';
    }
}
