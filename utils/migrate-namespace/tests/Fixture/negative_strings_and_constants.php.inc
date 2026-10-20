<?php

$display = 'Piwik';
define('PIWIK_CONSTANT', 1);
// This comment mentions Piwik\Foo.
/* Piwik\Foo in a block comment */
$t = PIWIK_CONSTANT * 2;
-----
<?php

$display = 'Piwik';
define('PIWIK_CONSTANT', 1);
// This comment mentions Piwik\Foo.
/* Piwik\Foo in a block comment */
$t = PIWIK_CONSTANT * 2;
