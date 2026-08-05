<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\ArchivingMetrics\Writer;

use Matomo\Plugins\ArchivingMetrics\Context;

interface WriterInterface
{
    public function write(Context $context, array $timing): void;
}
