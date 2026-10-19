<?php

$display = 'Piwik';
define('PIWIK_CONSTANT', 1);
const PIWIK_OTHER = 'Piwik\\Foo';
// This comment mentions Piwik\Foo.
/* Piwik\Foo in a block comment */
$t = PIWIK_CONSTANT + PIWIK_OTHER;
-----
<?php

$display = 'Piwik';
define('PIWIK_CONSTANT', 1);
const PIWIK_OTHER = 'Piwik\\Foo';
// This comment mentions Piwik\Foo.
/* Piwik\Foo in a block comment */
$t = PIWIK_CONSTANT + PIWIK_OTHER;
