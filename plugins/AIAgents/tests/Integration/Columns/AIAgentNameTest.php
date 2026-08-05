<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\AIAgents\tests\Integration\Columns;

use Matomo\Plugins\AIAgents\Columns\AIAgentName;
use Matomo\Plugins\AIAgents\Providers\ChatGPT;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visit\VisitProperties;
use Matomo\Tracker\Visitor;

/**
 * @group AIAgents
 * @group AIAgentsColumns
 * @group Plugins
 */
class AIAgentNameTest extends IntegrationTestCase
{
    /**
     * @var AIAgentName
     */
    private $aiAgentName;

    public function setUp(): void
    {
        parent::setUp();

        $this->aiAgentName = new AIAgentName();
    }

    public function testOnNewVisitShouldBeAbleToDetectAIAgent(): void
    {
        $visitor = $this->getNewVisitor();
        $request = $this->getRequest([
            ChatGPT::PARAM_SIGNATURE       => 'Signature (value irrelevant)',
            ChatGPT::PARAM_SIGNATURE_AGENT => '"https://chatgpt.com"',
            ChatGPT::PARAM_SIGNATURE_INPUT => 'Signature Input (value irrelevant)',
        ]);

        $expectedAgent = ChatGPT::getInstance()->getId();
        $actualAgent = $this->aiAgentName->onNewVisit($request, $visitor, null);

        self::assertSame($expectedAgent, $actualAgent);
    }

    public function testOnNewVisitShouldBeAbleToNotDetectAIAgent(): void
    {
        $visitor = $this->getNewVisitor();
        $request = $this->getRequest([]);

        $result = $this->aiAgentName->onNewVisit($request, $visitor, null);

        self::assertNull($result);
    }

    /**
     * @param array<string, string> $params
     */
    private function getRequest(array $params): Request
    {
        return new Request($params);
    }

    private function getNewVisitor(): Visitor
    {
        return new Visitor(new VisitProperties());
    }
}
