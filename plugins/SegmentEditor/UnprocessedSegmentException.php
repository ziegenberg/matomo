<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\SegmentEditor;

use Matomo\Matomo;
use Matomo\Segment;

class UnprocessedSegmentException extends \Exception
{
    private Segment $segment;

    private ?array $storedSegment;

    /**
     * @var bool
     */
    private $isSegmentToPreprocess;

    public function __construct(Segment $segment, $isSegmentToPreprocess, ?array $storedSegment = null)
    {
        parent::__construct(self::getErrorMessage($segment, $isSegmentToPreprocess, $storedSegment));

        $this->segment = $segment;
        $this->storedSegment = $storedSegment;
        $this->isSegmentToPreprocess = $isSegmentToPreprocess;
    }

    /**
     * @return Segment
     */
    public function getSegment()
    {
        return $this->segment;
    }

    /**
     * @return array|null
     */
    public function getStoredSegment()
    {
        return $this->storedSegment;
    }

    private static function getErrorMessage(Segment $segment, $isSegmentToPreprocess, ?array $storedSegment = null)
    {
        if (empty($storedSegment)) {
            // the segment was not created through the segment editor
            return Matomo::translate('SegmentEditor_CustomUnprocessedSegmentApiError1')
                . ' ' . Matomo::translate('SegmentEditor_CustomUnprocessedSegmentApiError2')
                . ' ' . Matomo::translate('SegmentEditor_CustomUnprocessedSegmentApiError3')
                . ' ' . Matomo::translate('SegmentEditor_CustomUnprocessedSegmentApiError4')
                . ' ' . Matomo::translate('SegmentEditor_CustomUnprocessedSegmentApiError5')
                . ' ' . Matomo::translate('SegmentEditor_CustomUnprocessedSegmentApiError6')
                . ' ' . Matomo::translate('SegmentEditor_UnprocessedSegmentInVisitorLog3');
        }

        $segmentName = !empty($storedSegment['name']) ? $storedSegment['name'] : $segment->getString();

        if (!$isSegmentToPreprocess) {
            // the segment was created in the segment editor, but set to be processed in real time
            return Matomo::translate('SegmentEditor_UnprocessedSegmentApiError1', [$segmentName, Matomo::translate('SegmentEditor_AutoArchiveRealTime')])
                . ' ' . Matomo::translate('SegmentEditor_UnprocessedSegmentApiError2', [Matomo::translate('SegmentEditor_AutoArchivePreProcessed')])
                . ' ' . Matomo::translate('SegmentEditor_UnprocessedSegmentApiError3');
        }

        // the segment is set to be processed during cron archiving, but has not been processed yet
        return Matomo::translate('SegmentEditor_UnprocessedSegmentNoData1', ['(' . $segmentName . ')'])
                . ' ' . Matomo::translate('SegmentEditor_UnprocessedSegmentNoData2')
                . ' ' . Matomo::translate('SegmentEditor_CustomUnprocessedSegmentApiError5')
                . ' ' . Matomo::translate('SegmentEditor_CustomUnprocessedSegmentApiError6')
                . ' ' . Matomo::translate('SegmentEditor_UnprocessedSegmentInVisitorLog3');
    }

    /**
     * @return bool
     */
    public function isSegmentToPreprocess()
    {
        return $this->isSegmentToPreprocess;
    }
}
