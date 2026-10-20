<?php

$a = sprintf('Piwik\\Plugins\\%s\\%s', $plugin, $class);
$b = sprintf('\Piwik\Plugins\%s\API', $plugin);
-----
<?php

$a = sprintf('Matomo\\Plugins\\%s\\%s', $plugin, $class);
$b = sprintf('\Matomo\Plugins\%s\API', $plugin);
