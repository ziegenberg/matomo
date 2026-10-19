<?php

use Piwik\Piwik;

Piwik::postEvent('MyPlugin.doSomething', ['answer' => 42]);
-----
<?php

use Matomo\Matomo;

Matomo::postEvent('MyPlugin.doSomething', ['answer' => 42]);
