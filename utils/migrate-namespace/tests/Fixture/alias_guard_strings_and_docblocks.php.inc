<?php

namespace App;

use Piwik\Cache as PiwikCache;

/**
 * @var PiwikCache
 * @var Piwik\Cache
 */
class Foo
{
    /** @var PiwikCache */
    public $a;

    public function bar()
    {
        $x = 'PiwikCache';
        $y = 'Piwik\Cache';
    }
}
-----
<?php

namespace App;

use Matomo\Cache as PiwikCache;

/**
 * @var PiwikCache
 * @var Matomo\Cache
 */
class Foo
{
    /** @var PiwikCache */
    public $a;

    public function bar()
    {
        $x = 'PiwikCache';
        $y = 'Matomo\Cache';
    }
}
