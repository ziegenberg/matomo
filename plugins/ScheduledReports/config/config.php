<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

use Matomo\Plugins\ScheduledReports\ReportEmailGenerator;
use Matomo\Plugins\ScheduledReports\ReportEmailGenerator\AttachedFileReportEmailGenerator;
use Matomo\Plugins\ScheduledReports\ReportEmailGenerator\HtmlReportEmailGenerator;

return [
    ReportEmailGenerator::class . '.pdf' => Matomo\DI::autowire(AttachedFileReportEmailGenerator::class)
        ->constructorParameter('attachedFileExtension', '.pdf')
        ->constructorParameter('attachedFileMimeType', 'application/pdf'),

    ReportEmailGenerator::class . '.csv' => Matomo\DI::autowire(AttachedFileReportEmailGenerator::class)
        ->constructorParameter('attachedFileExtension', '.csv')
        ->constructorParameter('attachedFileMimeType', 'application/csv'),

    ReportEmailGenerator::class . '.tsv' => Matomo\DI::autowire(AttachedFileReportEmailGenerator::class)
        ->constructorParameter('attachedFileExtension', '.tsv')
        ->constructorParameter('attachedFileMimeType', 'application/tsv'),

    ReportEmailGenerator::class . '.html' => Matomo\DI::create(HtmlReportEmailGenerator::class),
];
