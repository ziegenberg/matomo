<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Actions\Columns;

use Matomo\Columns\Dimension;

class SearchNoResultKeyword extends Dimension
{
    protected $type = self::TYPE_TEXT;
    protected $nameSingular = 'Actions_ColumnNoResultKeyword';
}
