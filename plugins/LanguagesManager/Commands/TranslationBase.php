<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\LanguagesManager\Commands;

use Matomo\Development;
use Matomo\Plugin\ConsoleCommand;

abstract class TranslationBase extends ConsoleCommand
{
    public function isEnabled()
    {
        return Development::isEnabled();
    }
}
