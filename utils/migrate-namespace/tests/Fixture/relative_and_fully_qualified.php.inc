<?php

namespace App;

use Piwik\Piwik;

Piwik\foo();
\Piwik\foo();
namespace\Piwik\foo();
-----
<?php

namespace App;

use Matomo\Matomo;

Matomo\foo();
\Matomo\foo();
namespace\Matomo\foo();
