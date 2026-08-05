<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreUpdater\Commands;

use Matomo\Filechecks;
use Matomo\SettingsServer;
use Matomo\Version;
use Matomo\Config;
use Matomo\DbHelper;
use Matomo\Filesystem;
use Matomo\Matomo;
use Matomo\Plugin\ConsoleCommand;
use Matomo\Plugins\CoreUpdater\Commands\Update\CliUpdateObserver;
use Matomo\Plugins\CoreUpdater\NoUpdatesFoundException;
use Matomo\Updater;

/**
 * @package CoreUpdater
 */
class Update extends ConsoleCommand
{
    /**
     * @var string[]
     */
    private $migrationQueries;

    protected function configure()
    {
        $this->setName('core:update');

        $this->setDescription(Matomo::translate('CoreUpdater_ConsoleCommandDescription'));

        $this->addNoValueOption('yes', null, Matomo::translate('CoreUpdater_ConsoleParameterDescription'));
        $this->addNoValueOption('skip-cache-clear', null, Matomo::translate('CoreUpdater_SkipCacheClearDesc'));
    }

    /**
     * Execute command like: ./console core:update --yes
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $skipCacheClear = $input->getOption('skip-cache-clear');
        try {
            if ($skipCacheClear) {
                $output->writeln(Matomo::translate('CoreUpdater_SkipCacheClear'));

                Filesystem::$skipCacheClearOnUpdate = true;
            }

            $this->executeClearCaches();

            $yes = $input->getOption('yes');

            try {
                $this->makeUpdate(true);

                if (!$yes) {
                    $yes = $this->askForConfirmation(
                        '<comment>' . Matomo::translate('CoreUpdater_ExecuteDbUpgrade') . ' (y/N) </comment>',
                        false
                    );
                }

                if ($yes) {
                    $output->writeln("\n" . Matomo::translate('CoreUpdater_ConsoleStartingDbUpgrade'));

                    $this->makeUpdate(false);

                    $this->writeSuccessMessage(Matomo::translate('CoreUpdater_PiwikHasBeenSuccessfullyUpgraded'));
                } else {
                    $this->writeSuccessMessage(Matomo::translate('CoreUpdater_DbUpgradeNotExecuted'));
                }

                $this->writeAlertMessageWhenCommandExecutedWithUnexpectedUser();
            } catch (NoUpdatesFoundException $e) {
                // Do not fail if no updates were found
                $this->writeSuccessMessage($e->getMessage());
            }
        } finally {
            Filesystem::$skipCacheClearOnUpdate = false;
        }

        return self::SUCCESS;
    }

    protected function executeClearCaches()
    {
        Filesystem::deleteAllCacheOnUpdate();
    }

    protected function makeUpdate($doDryRun)
    {
        $output = $this->getOutput();
        $this->checkAllRequiredOptionsAreNotEmpty();

        $updater = $this->makeUpdaterInstance();

        $componentsWithUpdateFile = $updater->getComponentUpdates();
        if (empty($componentsWithUpdateFile)) {
            throw new NoUpdatesFoundException(Matomo::translate('CoreUpdater_AlreadyUpToDate'));
        }

        $output->writeln(array(
            "",
            "    *** " . Matomo::translate('CoreUpdater_UpdateTitle') . " ***",
        ));

        // handle case of existing database with no tables
        if (!DbHelper::isInstalled()) {
            $this->handleCoreError(
                Matomo::translate('CoreUpdater_EmptyDatabaseError', Config::getInstance()->database['dbname'])
            );
            return;
        }

        $output->writeln(array(
            "",
            "    " . Matomo::translate('CoreUpdater_DatabaseUpgradeRequired'),
            "",
            "    " . Matomo::translate('CoreUpdater_YourDatabaseIsOutOfDate'),
        ));

        if ($this->isUpdatingCore($componentsWithUpdateFile)) {
            $currentVersion = $this->getCurrentVersionForCore($updater);
            $output->writeln(array(
                "",
                "    " . Matomo::translate('CoreUpdater_PiwikWillBeUpgradedFromVersionXToVersionY', array($currentVersion, Version::VERSION)),
            ));
        }

        $pluginsToUpdate = $this->getPluginsToUpdate($componentsWithUpdateFile);
        if (!empty($pluginsToUpdate)) {
            $output->writeln(array(
                "",
                "    " . Matomo::translate('CoreUpdater_TheFollowingPluginsWillBeUpgradedX', implode(', ', $pluginsToUpdate)),
            ));
        }

        $dimensionsToUpdate = $this->getDimensionsToUpdate($componentsWithUpdateFile);
        if (!empty($dimensionsToUpdate)) {
            $output->writeln(array(
                "",
                "    " . Matomo::translate('CoreUpdater_TheFollowingDimensionsWillBeUpgradedX', implode(', ', $dimensionsToUpdate)),
            ));
        }

        $output->writeln("");

        if ($doDryRun) {
            $this->doDryRun($updater);
        } else {
            $this->doRealUpdate($updater, $componentsWithUpdateFile);
        }
    }

    private function doDryRun(Updater $updater)
    {
        $output = $this->getOutput();
        $migrationQueries = $this->getMigrationQueriesToExecute($updater);

        if (empty($migrationQueries)) {
            $output->writeln(array("    *** " . Matomo::translate('CoreUpdater_ConsoleUpdateNoSqlQueries') . " ***", ""));
            return;
        }


        if ($updater->hasMajorDbUpdate()) {
            $output->writeln(array(
                "",
                sprintf("<comment>%s \n</comment>", Matomo::translate('CoreUpdater_MajorUpdateWarning1')),
            ));
        }

        $output->writeln(array("    *** " . Matomo::translate('CoreUpdater_DryRun') . " ***", ""));

        foreach ($migrationQueries as $query) {
            $result = $query->__toString();
            if (!empty($result)) {
                $output->writeln("    " . $result);
            }
        }

        $output->writeln(array("", "    *** " . Matomo::translate('CoreUpdater_DryRunEnd') . " ***", ""));
    }

    private function doRealUpdate(Updater $updater, $componentsWithUpdateFile)
    {
        $output = $this->getOutput();
        $output->writeln(array("    " . Matomo::translate('CoreUpdater_TheUpgradeProcessMayTakeAWhilePleaseBePatient'), ""));

        $updaterResult = $updater->updateComponents($componentsWithUpdateFile);

        if (@$updaterResult['coreError']) {
            $this->handleCoreError($updaterResult['errors'], $includeDiyHelp = true);
            return;
        }

        if (!empty($updaterResult['warnings'])) {
            $this->outputUpdaterWarnings($updaterResult['warnings']);
        }

        if (!empty($updaterResult['errors'])) {
            $this->outputUpdaterErrors($updaterResult['errors'], $updaterResult['deactivatedPlugins']);
        }

        if (
            !empty($updaterResult['warnings'])
            || !empty($updaterResult['errors'])
        ) {
            $output->writeln(array(
                "    " . Matomo::translate('CoreUpdater_HelpMessageIntroductionWhenWarning'),
                "",
                "    * " . $this->getUpdateHelpMessage(),
            ));
        }
    }

    private function handleCoreError($errors, $includeDiyHelp = false)
    {
        if (!is_array($errors)) {
            $errors = array($errors);
        }

        $output = $this->getOutput();
        $output->writeln(array(
            "",
            "    [X] " . Matomo::translate('CoreUpdater_CriticalErrorDuringTheUpgradeProcess'),
            "",
        ));

        foreach ($errors as $errorMessage) {
            $errorMessage = trim($errorMessage);
            $errorMessage = str_replace("\n", "\n    ", $errorMessage);

            $output->writeln("    * $errorMessage");
        }

        $output->writeln(array(
            "",
            "    " . Matomo::translate('CoreUpdater_HelpMessageIntroductionWhenError'),
            "",
            "    * " . $this->getUpdateHelpMessage(),
        ));

        if ($includeDiyHelp) {
            $output->writeln(array(
                "",
                "    " . Matomo::translate('CoreUpdater_ErrorDIYHelp'),
                "",
                "    * " . Matomo::translate('CoreUpdater_ErrorDIYHelp_1'),
                "    * " . Matomo::translate('CoreUpdater_ErrorDIYHelp_2'),
                "    * " . Matomo::translate('CoreUpdater_ErrorDIYHelp_3'),
                "    * " . Matomo::translate('CoreUpdater_ErrorDIYHelp_4'),
                "    * " . Matomo::translate('CoreUpdater_ErrorDIYHelp_5'),
            ));
        }

        throw new \RuntimeException(Matomo::translate('CoreUpdater_ConsoleUpdateFailure'));
    }

    private function outputUpdaterWarnings($warnings)
    {
        $output = $this->getOutput();
        $output->writeln(array(
            "",
            "    [!] " . Matomo::translate('CoreUpdater_WarningMessages'),
            "",
        ));

        foreach ($warnings as $message) {
            $output->writeln("    * $message");
        }
    }

    private function outputUpdaterErrors($errors, $deactivatedPlugins)
    {
        $output = $this->getOutput();
        $output->writeln(array(
            "",
            "    [X] " . Matomo::translate('CoreUpdater_ErrorDuringPluginsUpdates'),
            "",
        ));

        foreach ($errors as $message) {
            $output->writeln("    * $message");
        }

        if (!empty($deactivatedPlugins)) {
            $output->writeln(array(
                "",
                "    [!] " . Matomo::translate('CoreUpdater_WeAutomaticallyDeactivatedTheFollowingPlugins', implode(', ', $deactivatedPlugins)),
            ));
        }
    }

    private function getUpdateHelpMessage()
    {
        return Matomo::translate('CoreUpdater_HelpMessageContent', array('[',']',"\n    *"));
    }

    private function isUpdatingCore($componentsWithUpdateFile)
    {
        foreach ($componentsWithUpdateFile as $componentName => $updates) {
            if ($componentName == 'core') {
                return true;
            }
        }
        return false;
    }

    private function getCurrentVersionForCore(Updater $updater)
    {
        $currentVersion = $updater->getCurrentComponentVersion('core');
        if ($currentVersion === false) {
            $currentVersion = "<= 0.2.9";
        }
        return $currentVersion;
    }

    private function getPluginsToUpdate($componentsWithUpdateFile)
    {
        $plugins = array();
        foreach ($componentsWithUpdateFile as $componentName => $updates) {
            if (
                $componentName !== 'core'
                && 0 !== strpos($componentName, 'log_')
            ) {
                $plugins[] = $componentName;
            }
        }
        return $plugins;
    }

    private function getDimensionsToUpdate($componentsWithUpdateFile)
    {
        $dimensions = array();
        foreach ($componentsWithUpdateFile as $componentName => $updates) {
            if (0 === strpos($componentName, 'log_')) {
                $dimensions[] = $componentName;
            }
        }

        sort($dimensions);
        return $dimensions;
    }

    private function getMigrationQueriesToExecute(Updater $updater)
    {
        if (empty($this->migrationQueries)) {
            $this->migrationQueries = $updater->getSqlQueriesToExecute();
        }
        return $this->migrationQueries;
    }

    private function makeUpdaterInstance()
    {
        $updater = new Updater();

        $migrationQueryCount = count($this->getMigrationQueriesToExecute($updater));
        $updater->addUpdateObserver(new CliUpdateObserver($this->getOutput(), $migrationQueryCount));

        return $updater;
    }

    protected function writeAlertMessageWhenCommandExecutedWithUnexpectedUser()
    {
        if (SettingsServer::isWindows()) {
            // does not work on windows
            return;
        }

        $processUserAndGroup = Filechecks::getUserAndGroup();
        $fileOwnerUserAndGroup = Filechecks::getOwnerOfPiwikFiles();

        if (!$fileOwnerUserAndGroup || $processUserAndGroup == $fileOwnerUserAndGroup) {
            // current process user/group appear to be same as the Matomo filesystem user/group -> OK
            return;
        }
        $this->getOutput()->writeln(
            sprintf("<comment>%s</comment>", Matomo::translate('CoreUpdater_ConsoleUpdateUnexpectedUserWarning', [
                $processUserAndGroup,
                $fileOwnerUserAndGroup,
                Filechecks::getCommandToChangeOwnerOfPiwikFiles(),
            ]))
        );
    }
}
