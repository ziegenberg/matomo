<?php

$n = 'Piwik\DataTable\Filter\\';
$a = strlen('Piwik\DataTable\Filter\\');
if (str_starts_with($name, $n)) {
}
if (str_contains($name, 'Piwik\Plugins\\')) {
}
if (strpos($name, 'Piwik\Plugins\\') !== false) {
}
$b = str_replace('Piwik\Plugins\\', 'Matomo\Plugins\\', $name);
-----
<?php

$n = 'Matomo\DataTable\Filter\\';
$a = strlen('Matomo\DataTable\Filter\\');
if (str_starts_with($name, $n)) {
}
if (str_contains($name, 'Matomo\Plugins\\')) {
}
if (strpos($name, 'Matomo\Plugins\\') !== false) {
}
$b = str_replace('Matomo\Plugins\\', 'Matomo\Plugins\\', $name);
