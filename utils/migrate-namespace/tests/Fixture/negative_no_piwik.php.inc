<?php

namespace Matomo\Core;

use Matomo\Piwik;

class AlreadyMatomo
{
    public function method(): void
    {
        Matomo::postEvent('already', []);
    }
}
-----
<?php

namespace Matomo\Core;

use Matomo\Piwik;

class AlreadyMatomo
{
    public function method(): void
    {
        Matomo::postEvent('already', []);
    }
}
