<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Diagnostics;

use Matomo\ArchiveProcessor\Rules;
use Matomo\Notification;
use Matomo\Matomo;
use Matomo\Plugin;
use Matomo\Plugins\Diagnostics\Diagnostic\CronArchivingLastRunCheck;
use Matomo\Url;
use Matomo\View;

class Diagnostics extends Plugin
{
    public const NO_DATA_ARCHIVING_NOT_RUN_NOTIFICATION_ID = 'DiagnosticsNoDataArchivingNotRun';

    /**
     * @see \Matomo\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
            'Visualization.onNoData' => ['function' => 'onNoData', 'before' => true],
        );
    }

    public function getClientSideTranslationKeys(&$translations)
    {
        $translations[] = 'Diagnostics_ConfigFileTitle';
        $translations[] = 'Diagnostics_ConfigFileIntroduction';
        $translations[] = 'Diagnostics_HideUnchanged';
        $translations[] = 'Diagnostics_Sections';
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/Diagnostics/stylesheets/configfile.less";
    }

    public function onNoData(View $dataTableView)
    {
        if (!Matomo::isUserHasSomeAdminAccess()) {
            return;
        }

        if (Rules::isBrowserTriggerEnabled()) {
            return;
        }

        $lastSuccessfulRun = CronArchivingLastRunCheck::getTimeSinceLastSuccessfulRun();
        if ($lastSuccessfulRun > CronArchivingLastRunCheck::SECONDS_IN_DAY) {
            $content = Matomo::translate('Diagnostics_NoDataForReportArchivingNotRun', [
                Url::getExternalLinkTag('https://matomo.org/docs/setup-auto-archiving/'),
                '</a>',
            ]);

            $notification = new Notification($content);
            $notification->priority = Notification::PRIORITY_HIGH;
            $notification->context = Notification::CONTEXT_INFO;
            $notification->flags = Notification::FLAG_NO_CLEAR;
            $notification->type = Notification::TYPE_TRANSIENT;
            $notification->raw = true;

            $dataTableView->notifications[self::NO_DATA_ARCHIVING_NOT_RUN_NOTIFICATION_ID] = $notification;
        }
    }
}
