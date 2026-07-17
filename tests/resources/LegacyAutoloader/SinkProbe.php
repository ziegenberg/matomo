<?php

namespace Matomo\Test;

/**
 * Fixture for LegacyAutoLoaderTest sink coverage. A fresh Matomo\ class not referenced
 * elsewhere, so `Piwik\Test\SinkProbe` (prefix-swapped to it) fires the autoloader
 * cleanly instead of hitting PHP's per-class cache.
 */
class SinkProbe
{
}
