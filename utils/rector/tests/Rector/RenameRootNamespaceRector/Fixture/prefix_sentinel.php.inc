<?php

$a = strpos($name, 'Piwik\Plugins') === 0;
$b = false === strpos($vizClass, 'Piwik\\Plugins\\CoreVisualizations');
-----
<?php

$a = strpos($name, 'Matomo\Plugins') === 0;
$b = false === strpos($vizClass, 'Matomo\Plugins\CoreVisualizations');
