<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\VisitorInterest;

use Matomo\FrontController;
use Matomo\Matomo;

class VisitorInterest extends \Matomo\Plugin
{
    public function postLoad()
    {
        Matomo::addAction('Template.footerVisitsFrequency', array('Matomo\Plugins\VisitorInterest\VisitorInterest', 'footerVisitsFrequency'));
    }

    public static function footerVisitsFrequency(&$out)
    {
        $out .= FrontController::getInstance()->fetchDispatch('VisitorInterest', 'index');
    }
}
