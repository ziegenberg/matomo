<?php

namespace Matomo\Test;

/**
 * Separate fresh fixture for the default-sink test, so it isn't masked by SinkProbe being
 * cached after the explicit-sink test runs in the same process.
 */
class DefaultSinkProbe
{
}
