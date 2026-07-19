<?php

namespace Matomo;

/**
 * Stand-in Matomo\ class used by LegacyAutoLoaderTest as the exceptions-map
 * target for a class whose short name changes (a stand-in for the
 * Matomo\Matomo -> Matomo\Matomo facade rename). It is intentionally named
 * differently from the Piwik\ source so the test can prove the exceptions map
 * redirected the lookup instead of prefix-swapping it.
 */
class RenamedFacade
{
}
