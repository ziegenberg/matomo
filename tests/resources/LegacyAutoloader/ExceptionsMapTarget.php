<?php

namespace Matomo\Test;

/**
 * Fixture for LegacyAutoLoaderTest exceptions-map coverage. Lives under the Matomo\
 * root so the LegacyAutoloader can alias a Piwik\ name to it; it is manually required
 * by the test so the class exists before the alias is requested.
 */
class ExceptionsMapTarget
{
}
