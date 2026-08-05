<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreAdminHome\tests\Framework\Mock;

class API extends \Matomo\Plugins\CoreAdminHome\API
{
    private $invalidatedReports = array();

    public function invalidateArchivedReports(
        $idSites,
        $dates,
        $period = false,
        $segment = false,
        $cascadeDown = false,
        $_forceInvalidateNonexistent = false
    ): array {
        $this->invalidatedReports[] = func_get_args();

        return [];
    }

    public function getInvalidatedReports()
    {
        return $this->invalidatedReports;
    }
}
