<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Unit;

use Matomo\CronArchive;
use Matomo\Date;
use Matomo\Version;
use ReflectionClass;

/**
 * @group DeprecatedMethodsTest
 * @group Core
 */
class DeprecatedMethodsTest extends \PHPUnit\Framework\TestCase
{
    public function testDeprecations()
    {
        $this->assertDeprecatedMethodIsRemovedInPiwik3b1('Matomo\SettingsServer', 'isApache');

        $validTill = '2015-03-10';
        $this->assertDeprecatedMethodIsRemovedBeforeDate('\Matomo\Period', 'factory', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('\Matomo\Config', 'getConfigSuperUserForBackwardCompatibility', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('\Matomo\Menu\MenuAdmin', 'addEntry', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('\Matomo\Menu\MenuAdmin', 'removeEntry', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('\Matomo\Menu\MenuTop', 'addEntry', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('\Matomo\Menu\MenuTop', 'removeEntry', $validTill);

        $validTill = '2015-03-10';
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\IP', 'sanitizeIp', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\IP', 'sanitizeIpRange', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\IP', 'P2N', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\IP', 'N2P', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\IP', 'prettyPrint', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\IP', 'isIPv4', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\IP', 'long2ip', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\IP', 'isIPv6', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\IP', 'isMappedIPv4', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\IP', 'getIPv4FromMappedIPv6', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\IP', 'getIpsForRange', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\IP', 'isIpInRange', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\IP', 'getHostByAddr', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\SettingsPiwik', 'rewriteTmpPathWithInstanceId', $validTill);

        $validTill = '2015-05-01';
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\Plugins\UserSettings\API', 'getBrowserVersion', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\Plugins\UserSettings\API', 'getBrowser', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\Plugins\UserSettings\API', 'getOS', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\Plugins\UserSettings\API', 'getOSFamily', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\Plugins\UserSettings\API', 'getBrowserType', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\Plugins\UserSettings\API', 'getMobileVsDesktop', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\Plugins\UserSettings\API', 'getResolution', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\Plugins\UserSettings\API', 'getConfiguration', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\Plugins\UserSettings\API', 'getPlugin', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\Plugins\UserSettings\API', 'getLanguage', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\Plugins\UserSettings\API', 'getLanguageCode', $validTill);
        $this->assertDeprecatedMethodIsRemovedBeforeDate('Matomo\Plugins\UserSettings\UserSettings', 'renameDeprecatedModuleAndAction', $validTill);

        // please be aware if re-adding a plugin called userSettings, and someone updates eg from Piwik 2.13 to that version,
        // the plugin will be possibly removed in an Update during 2.14.0
        $this->assertDeprecatedClassIsRemoved('Matomo\Plugins\UserSettings\UserSettings', $validTill);

        $this->assertDeprecatedMethodIsRemovedInPiwik3('\Matomo\Menu\MenuAbstract', 'add');
        $this->assertDeprecatedMethodIsRemovedInPiwik3('\Matomo\Archive', 'getDataTableFromArchive');
        $this->assertDeprecatedMethodIsRemovedInPiwik3('\Matomo\Plugins\API\API', 'getLastDate');

        $this->assertDeprecatedMethodIsRemovedInPiwik3('Matomo\Plugins\DevicesDetection\DevicesDetection', 'renameUserSettingsModuleAndAction');
        $this->assertDeprecatedMethodIsRemovedInPiwik3('Matomo\Plugins\Resolution\Resolution', 'renameUserSettingsModuleAndAction');
        $this->assertDeprecatedMethodIsRemovedInPiwik3('Matomo\Plugins\DevicePlugins\DevicePlugins', 'renameUserSettingsModuleAndAction');
        $this->assertDeprecatedMethodIsRemovedInPiwik3('Matomo\Plugins\UserLanguage\UserLanguage', 'renameUserSettingsModuleAndAction');

        $this->assertDeprecatedMethodIsRemovedInMatomo4('\Matomo\Plugin', 'getListHooksRegistered');
        $this->assertDeprecatedMethodIsRemovedInMatomo4('Matomo\Updates', 'getSql');
        $this->assertDeprecatedMethodIsRemovedInMatomo4('Matomo\Updates', 'update');
        $this->assertDeprecatedMethodIsRemovedInMatomo4('Matomo\Updates', 'getMigrationQueries');
        $this->assertDeprecatedMethodIsRemovedInMatomo4('Matomo\Updater', 'executeMigrationQueries');

        $this->assertDeprecatedMethodIsRemovedInMatomo4('Matomo\SettingsPiwik', 'isPiwikInstalled');
        $this->assertDeprecatedMethodIsRemovedInMatomo4('Matomo\Matomo', 'doAsSuperUser');

        $validTill = '2021-03-01';
        $this->assertDeprecatedMethodIsRemovedBeforeDate(CronArchive::class, 'checkNoDanglingInvalidations', $validTill);

        $this->assertDeprecatedMethodIsRemovedInMatomo7('Matomo\Common', 'getRequestVar');
        $this->assertDeprecatedMethodIsRemovedInMatomo6('Matomo\Plugins\Overlay\API', 'getExcludedQueryParameters');
        $this->assertDeprecatedMethodIsRemovedInMatomo6('Matomo\Db', 'isOptimizeInnoDBSupported');
        $this->assertDeprecatedMethodIsRemovedInMatomo6('Matomo\Db', 'optimizeTables');
        $this->assertDeprecatedMethodIsRemovedInMatomo6('Matomo\Db\TransactionLevel', 'setUncommitted');
        $this->assertDeprecatedMethodIsRemovedInMatomo6('Matomo\Plugins\SitesManager\API', 'setGlobalExcludedQueryParameters');
        $this->assertDeprecatedMethodIsRemovedInMatomo6('Matomo\API\Request', 'isTokenAuthProvidedSecurely');
        $this->assertDeprecatedMethodIsRemovedInMatomo7('Matomo\DataAccess\ArchiveTableCreator', 'triggerLegacyDefaultDeprecation');
    }


    private function assertDeprecatedMethodIsRemovedBeforeDate($className, $method, $removalDate)
    {
        $now         = Date::now();
        $removalDate = Date::factory($removalDate);

        if (!class_exists($className)) {
            return;
        }

        $class        = new ReflectionClass($className);
        $methodExists = $class->hasMethod($method);

        if (!$now->isLater($removalDate)) {
            $errorMessage = $className . '::' . $method . ' should still exists until ' . $removalDate . ' although it is deprecated.';
            $this->assertTrue($methodExists, $errorMessage);
            return;
        }

        $errorMessage = $className . '::' . $method . ' should be removed as the method is deprecated but it is not.';
        $this->assertFalse($methodExists, $errorMessage);
    }


    private function assertDeprecatedClassIsRemoved($className, $removalDate)
    {
        $now         = Date::now();
        $removalDate = Date::factory($removalDate);

        $classExists = class_exists($className);

        if (!$now->isLater($removalDate)) {
            $errorMessage = $className . ' should still exists until ' . $removalDate . ' although it is deprecated.';
            $this->assertTrue($classExists, $errorMessage);
            return;
        }

        $errorMessage = $className . ' should be removed as the method is deprecated but it is not.';
        $this->assertFalse($classExists, $errorMessage);
    }

    private function assertDeprecatedMethodIsRemovedInPiwik3b1($className, $method)
    {
        $this->assertDeprecatedMethodIsRemovedInPiwikVersion('3.0.0-b1', $className, $method);
    }

    private function assertDeprecatedMethodIsRemovedInPiwik3($className, $method)
    {
        $this->assertDeprecatedMethodIsRemovedInPiwikVersion('3.0.0-b2', $className, $method);
    }

    private function assertDeprecatedMethodIsRemovedInMatomo4($className, $method)
    {
        $this->assertDeprecatedMethodIsRemovedInPiwikVersion('4.0.0-b1', $className, $method);
    }

    private function assertDeprecatedMethodIsRemovedInMatomo6($className, $method)
    {
        $this->assertDeprecatedMethodIsRemovedInPiwikVersion('6.0.0-b1', $className, $method);
    }

    private function assertDeprecatedMethodIsRemovedInMatomo7($className, $method)
    {
        $this->assertDeprecatedMethodIsRemovedInPiwikVersion('7.0.0-b1', $className, $method);
    }

    private function assertDeprecatedMethodIsRemovedInPiwikVersion($piwikVersion, $className, $method)
    {
        $version = Version::VERSION;

        $class        = new ReflectionClass($className);
        $methodExists = $class->hasMethod($method);

        if (-1 === version_compare($version, $piwikVersion)) {
            $errorMessage = $className . '::' . $method . ' should still exists until ' . $piwikVersion . ' although it is deprecated.';
            $this->assertTrue($methodExists, $errorMessage);
            return;
        }

        $errorMessage = $className . '::' . $method . ' should be removed as the method is deprecated but it is not.';
        $this->assertFalse($methodExists, $errorMessage);
    }
}
