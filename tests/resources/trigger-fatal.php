<?php

ini_set('display_errors', 0);
define('PIWIK_PRINT_ERROR_BACKTRACE', true);
define('PIWIK_ENABLE_DISPATCH', false);

require_once __DIR__ . '/../../tests/PHPUnit/proxy/index.php';

$environment = new \Matomo\Application\Environment(null);
$environment->init();

\Matomo\Access::getInstance()->setSuperUserAccess(true);

class MyClass
{
    public function triggerError($arg1, $arg2)
    {
        try {
            \Matomo\ErrorHandler::pushFatalErrorBreadcrumb(static::class, ['arg1' => $arg1, 'arg2' => $arg2]);

            $val = "";
            while (true) {
                $val .= str_repeat("*", 1024 * 1024 * 1024);
            }
        } finally {
            \Matomo\ErrorHandler::popFatalErrorBreadcrumb();
        }
    }

    public static function staticMethod()
    {
        try {
            \Matomo\ErrorHandler::pushFatalErrorBreadcrumb(static::class);

            $instance = new MyClass();
            $instance->triggerError('argval', 'another');
        } finally {
            \Matomo\ErrorHandler::popFatalErrorBreadcrumb();
        }
    }
}

class MyDerivedClass extends MyClass
{
}

function myFunction()
{
    try {
        \Matomo\ErrorHandler::pushFatalErrorBreadcrumb();

        MyDerivedClass::staticMethod();
    } finally {
        \Matomo\ErrorHandler::popFatalErrorBreadcrumb();
    }
}

myFunction();
