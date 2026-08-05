<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3.0 or later
 */

namespace Matomo;

/**
 * Stand-in `Matomo\` class used by LegacyAutoLoaderTest to exercise the
 * bootstrap catch-up pass: it is loaded via direct `require_once` (bypassing
 * the autoloader, so no `Piwik\` alias is created eagerly), then the catch-up
 * pass is expected to alias it to `Piwik\EagerAliasTarget`.
 */
class EagerAliasTarget
{
}
