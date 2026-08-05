<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Unit\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Matomo\Tests\PHPStan\Rules\JsonResponseReturnTypeRule;

/**
 * @group Core
 * @extends RuleTestCase<JsonResponseReturnTypeRule>
 */
class JsonResponseReturnTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new JsonResponseReturnTypeRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/JsonResponseController.php'], [
            [
                'Controller action missingReturnType() is marked #[\Matomo\Http\JsonResponse] and must'
                . ' declare a "string" return type.',
                58,
            ],
            [
                'Controller action wrongReturnType() is marked #[\Matomo\Http\JsonResponse] and must declare'
                . ' a "string" return type.',
                64,
            ],
        ]);
    }
}
