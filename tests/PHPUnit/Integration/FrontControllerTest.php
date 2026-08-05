<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration;

use Matomo\Access;
use Matomo\Auth;
use Matomo\Container\StaticContainer;
use Matomo\FrontController;
use Matomo\Http;
use Matomo\Session;
use Matomo\Session\SessionFingerprint;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

class FrontControllerTest extends IntegrationTestCase
{
    public function testFatalErrorStackTracesReturned()
    {
        $url = Fixture::getRootUrl() . '/tests/resources/trigger-fatal.php?format=json';
        $response = Http::sendHttpRequest($url, self::isCIEnvironment() ? 5 : 20);

        $response = json_decode($response, $isAssoc = true);
        $response['message'] = $this->cleanMessage($response['message']);

        $this->assertEquals('error', $response['result']);

        $expectedFormat = <<<FORMAT
Allowed memory size of %s bytes exhausted (tried to allocate %s bytes) on /tests/resources/trigger-fatal.php(23) #0 /tests/resources/trigger-fatal.php(36): MyClass-&gt;triggerError(arg1=&quot;argval&quot;, arg2=&quot;another&quot;) #1 /tests/resources/trigger-fatal.php(52): MyDerivedClass::staticMethod() #2 /tests/resources/trigger-fatal.php(58): myFunction()
FORMAT;

        $this->assertStringMatchesFormat($expectedFormat, $response['message']);
    }

    public function testThrownExceptionInFrontControllerPrintsBacktrace()
    {
        $url = Fixture::getRootUrl() . '/tests/resources/trigger-fatal-exception.php?format=json';
        $response = Http::sendHttpRequest($url, self::isCIEnvironment() ? 5 : 20);

        $response = json_decode($response, $isAssoc = true);
        $response['message'] = $this->cleanMessage($response['message']);

        $this->assertEquals('error', $response['result']);

        $expectedFormat = <<<FORMAT
test message on /tests/resources/trigger-fatal-exception.php(23) #0 [internal function]: {closure}('CoreHome', 'index', Array) #1 /core/EventDispatcher.php(141): call_user_func_array(Object(Closure), Array) #2 /core/Matomo.php(845): Matomo\EventDispatcher-&gt;postEvent('Request.dispatc...', Array, false, Array) #3 /core/FrontController.php(606): Matomo\Matomo::postEvent('Request.dispatc...', Array) #4 /core/FrontController.php(168): Matomo\FrontController-&gt;doDispatch('CoreHome', 'index', Array) #5 /tests/resources/trigger-fatal-exception.php(31): Matomo\FrontController-&gt;dispatch('CoreHome', 'index') #6 {main}
FORMAT;

        if (version_compare(PHP_VERSION, '8.4.0-dev', '>=')) {
            $expectedFormat = <<<FORMAT
test message on /tests/resources/trigger-fatal-exception.php(23) #0 [internal function]: {closure:/tests/resources/trigger-fatal-exception.php:20}('...', '...', Array) #1 /core/EventDispatcher.php(147): call_user_func_array(Object(Closure), Array) #2 /core/Matomo.php(880): Matomo\EventDispatcher-&gt;postEvent('...', Array, false, Array) #3 /core/FrontController.php(625): Matomo\Matomo::postEvent('...', Array) #4 /core/FrontController.php(169): Matomo\FrontController-&gt;doDispatch('...', '...', Array) #5 /tests/resources/trigger-fatal-exception.php(31): Matomo\FrontController-&gt;dispatch('...', '...') #6 {main}
FORMAT;
        }

        //remove all the numbers
        $expectedFormat = preg_replace('/[0-9]+/', 'x', $expectedFormat);
        $expectedFormat = preg_replace('/".*?"|\'.*?\'/', 'xxx', $expectedFormat);

        $actualFormat = preg_replace('/[0-9]+/', 'x', $response['message']);
        $actualFormat = preg_replace('/".*?"|\'.*?\'/', 'xxx', $actualFormat);

        $this->assertStringMatchesFormat($expectedFormat, $actualFormat);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAuthImplementationConfiguredEvenIfSessionAuthSucceeds()
    {
        Session::start();

        Access::getInstance()->setSuperUserAccess(false);

        $sessionFingerprint = new SessionFingerprint();
        $sessionFingerprint->initialize('superUserLogin', Fixture::getTokenAuth());

        FrontController::getInstance()->init();

        /** @var \Matomo\Plugins\Login\Auth $auth */
        $auth = StaticContainer::get(Auth::class);
        $this->assertInstanceOf(\Matomo\Plugins\Login\Auth::class, $auth);

        $this->assertEquals('superUserLogin', $auth->getLogin());
        $this->assertEquals(Fixture::getTokenAuth(), $auth->getTokenAuth());
    }

    private function cleanMessage($message)
    {
        return trim($message);
    }

    /**
     * @param Fixture $fixture
     */
    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);
        $fixture->createSuperUser = true;
    }
}
