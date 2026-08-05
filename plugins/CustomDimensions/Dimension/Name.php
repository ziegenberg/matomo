<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CustomDimensions\Dimension;

use Exception;
use Matomo\Matomo;

class Name
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function check()
    {
        $maxLen = 255;

        if (empty($this->name)) {
            throw new Exception(Matomo::translate('CustomDimensions_NameIsRequired'));
        }

        if (strlen($this->name) > $maxLen) {
            throw new Exception(Matomo::translate('CustomDimensions_NameIsTooLong', $maxLen));
        }

        $blockedCharacters = self::getBlockedCharacters();

        // we do not really have to do this and it is not very effective for preventing XSS but doesn't hurt to have
        if (strip_tags($this->name) !== $this->name || str_replace($blockedCharacters, '', $this->name) !== $this->name) {
            throw new Exception(Matomo::translate('CustomDimensions_NameAllowedCharacters'));
        }
    }

    /**
     * @api
     */
    public static function getBlockedCharacters()
    {
        return [
            '/', '\\', '&', '.', '<', '>',
        ];
    }
}
