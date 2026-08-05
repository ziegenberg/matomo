<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\ScheduledReports\ReportEmailGenerator;

use Matomo\Mail;
use Matomo\Plugins\ScheduledReports\GeneratedReport;
use Matomo\Plugins\ScheduledReports\ReportEmailGenerator;

class HtmlReportEmailGenerator extends ReportEmailGenerator
{
    protected function configureEmail(Mail $mail, GeneratedReport $report)
    {
        // Needed when using images as attachment with cid
        $mail->setBodyHtml($report->getContents());
    }
}
