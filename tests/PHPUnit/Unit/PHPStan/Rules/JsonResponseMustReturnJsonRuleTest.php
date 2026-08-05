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
use Matomo\Tests\PHPStan\Rules\JsonResponseMustReturnJsonRule;

/**
 * @group Core
 * @extends RuleTestCase<JsonResponseMustReturnJsonRule>
 */
class JsonResponseMustReturnJsonRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new JsonResponseMustReturnJsonRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/JsonResponseController.php'], [
            [
                'Controller action attributedMixedReturn() is marked #[\Matomo\Http\JsonResponse] but'
                . ' returns a non-JSON value on this path; such an action must return JSON on every'
                . ' path. Split it into separate actions (or remove the attribute).',
                216,
            ],
        ]);
    }
}
