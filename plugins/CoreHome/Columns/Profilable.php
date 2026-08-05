<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Columns;

use Matomo\Plugin\Dimension\VisitDimension;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visitor;

class Profilable extends VisitDimension
{
    public const COLUMN_TYPE = 'TINYINT(1) NULL';
    protected $columnName = 'profilable';
    protected $columnType = self::COLUMN_TYPE;
    protected $nameSingular = 'CoreHome_Profilable';
    protected $segmentName = 'profilable';
    protected $type = self::TYPE_BOOL;
    protected $acceptValues = 'CoreHome_ProfilableSegmentHelp';

    public function __construct()
    {
        $this->suggestedValuesCallback = function ($idSite, $maxValuesToReturn) {
            return ['0', '1'];
        };
    }

    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        $value = $request->getVisitorId();

        if (empty($value)) {
            return 0;
        }

        return 1;
    }

    public function onExistingVisit(Request $request, Visitor $visitor, $action)
    {
        if ($visitor->getVisitorColumn($this->columnName)) {
            // once it is 1, we don't set it back to 0 if user disables cookies later cause user would be still idenfied
            // for this same visit with the fingerprint
            return 1;
        }

        return $this->onNewVisit($request, $visitor, $action);
    }
}
