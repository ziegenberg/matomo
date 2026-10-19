<?php

namespace App;

use Piwik\Cache as Piwik;
use Piwik\Cache as PiwikCache;

Piwik::get('key');
PiwikCache::get('key');
-----
<?php

namespace App;

use Matomo\Cache as Piwik;
use Matomo\Cache as PiwikCache;

Piwik::get('key');
PiwikCache::get('key');
