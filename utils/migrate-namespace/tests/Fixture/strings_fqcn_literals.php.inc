<?php

$a = 'Piwik\Foo';
$b = "Piwik\Foo";
$c = '\Piwik\Foo';
$d = array('\Piwik\Piwik', 'translate');
$e = 'Piwik\Piwik';
$f = class_exists('Piwik\Foo');
$g = StaticContainer::get('Piwik\Auth');
$h = "must be an instance of Piwik\Tracker\Handler";
const PIWIK_OTHER = 'Piwik\\Foo';
define('PIWIK_DEFINED', 'Piwik\Foo');
-----
<?php

$a = 'Matomo\Foo';
$b = "Matomo\Foo";
$c = '\Matomo\Foo';
$d = array('\Matomo\Matomo', 'translate');
$e = 'Matomo\Matomo';
$f = class_exists('Matomo\Foo');
$g = StaticContainer::get('Matomo\Auth');
$h = "must be an instance of Matomo\Tracker\Handler";
const PIWIK_OTHER = 'Matomo\\Foo';
define('PIWIK_DEFINED', 'Matomo\Foo');
