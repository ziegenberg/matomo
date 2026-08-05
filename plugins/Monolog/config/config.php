<?php

use Matomo\Container\Container;
use Matomo\Log\Logger;
use Matomo\Log;
use Matomo\Plugins\Monolog\Handler\FileHandler;
use Matomo\Plugins\Monolog\Handler\LogCaptureHandler;

return array(

    Logger::class => Matomo\DI::create(Logger::class)
        ->constructor('piwik', Matomo\DI::get('log.handlers'), Matomo\DI::get('log.processors')),

    Log\LoggerInterface::class => Matomo\DI::get(Logger::class),

    // For BC reasons
    'Monolog\Logger' =>  Matomo\DI::get(Logger::class),
    'Psr\Log\LoggerInterface' => Matomo\DI::get(Log\LoggerInterface::class),

    'log.handler.classes' => array(
        'file'     => 'Matomo\Plugins\Monolog\Handler\FileHandler',
        'screen'   => 'Matomo\Plugins\Monolog\Handler\WebNotificationHandler',
        'database' => 'Matomo\Plugins\Monolog\Handler\DatabaseHandler',
        'errorlog' => 'Matomo\Plugins\Monolog\Handler\ErrorLogHandler',
        'syslog' => 'Matomo\Plugins\Monolog\Handler\SyslogHandler',
    ),
    'log.handlers' => Matomo\DI::factory(function (Container $c) {
        if ($c->has('ini.log.log_writers')) {
            $writerNames = $c->get('ini.log.log_writers');
        } else {
            return array();
        }

        $classes = $c->get('log.handler.classes');

        $logConfig = $c->get(\Matomo\Config::class)->log;
        $enableFingersCrossed = isset($logConfig['enable_fingers_crossed_handler']) && $logConfig['enable_fingers_crossed_handler'] == 1;
        $fingersCrossedStopBuffering = isset($logConfig['fingers_crossed_stop_buffering_on_activation']) && $logConfig['fingers_crossed_stop_buffering_on_activation'] == 1;
        $enableLogCaptureHandler = isset($logConfig['enable_log_capture_handler']) && $logConfig['enable_log_capture_handler'] == 1;

        $isLogBufferingAllowed = !\Matomo\Common::isPhpCliMode()
            || \Matomo\SettingsServer::isArchivePhpTriggered()
            || \Matomo\CliMulti::isCliMultiRequest();

        $writerNames = array_map('trim', $writerNames);

        $writers = [];
        foreach ($writerNames as $writerName) {
            if (
                $writerName === 'screen'
                && \Matomo\Common::isPhpCliMode()
                && !defined('PIWIK_TEST_MODE')
                && !\Matomo\SettingsServer::isTrackerApiRequest()
            ) {
                continue; // screen writer is only valid for web requests (except for tracker CLI requests)
            }

            if (isset($classes[$writerName])) {
                // wrap the handler in FingersCrossedHandler if we can and this isn't the screen handler

                /** @var \Monolog\Handler\HandlerInterface $handler */
                $handler = $c->make($classes[$writerName]);
                if (
                    $enableFingersCrossed
                    && $writerName !== 'screen'
                    && $handler instanceof \Monolog\Handler\AbstractHandler
                    && $isLogBufferingAllowed
                ) {
                    $passthruLevel = $handler->getLevel();

                    $handler->setLevel(Logger::DEBUG);

                    $handler = new \Monolog\Handler\FingersCrossedHandler(
                        $handler,
                        $activationStrategy = null,
                        $bufferSize = 0,
                        $bubble = true,
                        $fingersCrossedStopBuffering,
                        $passthruLevel
                    );
                }

                $writers[$writerName] = $handler;
            }
        }

        if (
            $enableLogCaptureHandler
            && $isLogBufferingAllowed
        ) {
            $writers[] = $c->get(LogCaptureHandler::class);
        }

        // we always add the null handler to make sure there is at least one handler specified. otherwise Monolog will
        // add a stream handler to stderr w/ a DEBUG log level, which will cause archiving requests to fail.
        if (empty($writers)) {
            $writers[] = $c->get(\Monolog\Handler\NullHandler::class);
        }

        return array_values($writers);
    }),

    'log.processors' => array(
        Matomo\DI::get('Matomo\Plugins\Monolog\Processor\SprintfProcessor'),
        Matomo\DI::get('Matomo\Plugins\Monolog\Processor\ClassNameProcessor'),
        Matomo\DI::get('Matomo\Plugins\Monolog\Processor\RequestIdProcessor'),
        Matomo\DI::get('Matomo\Plugins\Monolog\Processor\ExceptionToTextProcessor'),
        Matomo\DI::get('Monolog\Processor\PsrLogMessageProcessor'),
        Matomo\DI::get('Matomo\Plugins\Monolog\Processor\TokenProcessor'),
    ),

    'Matomo\Plugins\Monolog\Handler\FileHandler' => Matomo\DI::create()
        ->constructor(Matomo\DI::get('log.file.filename'), Matomo\DI::get('log.level.file'))
        ->method('setFormatter', Matomo\DI::get('log.lineMessageFormatter.file')),

    'Matomo\Plugins\Monolog\Handler\ErrorLogHandler' => Matomo\DI::autowire()
        ->constructorParameter('level', Matomo\DI::get('log.level.errorlog'))
        ->method('setFormatter', Matomo\DI::get('log.lineMessageFormatter.file')),

    'Matomo\Plugins\Monolog\Handler\SyslogHandler' => Matomo\DI::autowire()
        ->constructorParameter('ident', Matomo\DI::get('log.syslog.ident'))
        ->constructorParameter('level', Matomo\DI::get('log.level.syslog'))
        ->method('setFormatter', Matomo\DI::get('log.lineMessageFormatter.file')),

    'Matomo\Plugins\Monolog\Handler\DatabaseHandler' => Matomo\DI::create()
        ->constructor(Matomo\DI::get('log.level.database'))
        ->method('setFormatter', Matomo\DI::get('log.lineMessageFormatter')),

    'Matomo\Plugins\Monolog\Handler\WebNotificationHandler' => Matomo\DI::create()
        ->constructor(Matomo\DI::get('log.level.screen'))
        ->method('setFormatter', Matomo\DI::get('log.lineMessageFormatter')),

    'log.level' => Matomo\DI::factory(function (Container $c) {
        if ($c->has('ini.log.log_level')) {
            $level = strtoupper($c->get('ini.log.log_level'));
            if (!empty($level) && defined('Matomo\Log::' . strtoupper($level))) {
                return Log::getMonologLevel(constant('Matomo\Log::' . strtoupper($level)));
            }
        }

        return Logger::WARNING;
    }),

    'log.level.file' => Matomo\DI::factory(function (Container $c) {
        if ($c->has('ini.log.log_level_file')) {
            $level = Log::getMonologLevelIfValid($c->get('ini.log.log_level_file'));
            if ($level !== null) {
                return $level;
            }
        }
        return $c->get('log.level');
    }),

    'log.level.screen' => Matomo\DI::factory(function (Container $c) {
        if ($c->has('ini.log.log_level_screen')) {
            $level = Log::getMonologLevelIfValid($c->get('ini.log.log_level_screen'));
            if ($level !== null) {
                return $level;
            }
        }
        return $c->get('log.level');
    }),

    'log.level.database' => Matomo\DI::factory(function (Container $c) {
        if ($c->has('ini.log.log_level_database')) {
            $level = Log::getMonologLevelIfValid($c->get('ini.log.log_level_database'));
            if ($level !== null) {
                return $level;
            }
        }
        return $c->get('log.level');
    }),

    'log.level.syslog' => Matomo\DI::factory(function (Container $c) {
        if ($c->has('ini.log.log_level_syslog')) {
            $level = Log::getMonologLevelIfValid($c->get('ini.log.log_level_syslog'));
            if ($level !== null) {
                return $level;
            }
        }
        return $c->get('log.level');
    }),

    'log.level.errorlog' => Matomo\DI::factory(function (Container $c) {
        if ($c->has('ini.log.log_level_errorlog')) {
            $level = Log::getMonologLevelIfValid($c->get('ini.log.log_level_errorlog'));
            if ($level !== null) {
                return $level;
            }
        }
        return $c->get('log.level');
    }),

    'log.file.filename' => Matomo\DI::factory(function (Container $c) {
        $logPath = $c->get('ini.log.logger_file_path');

        // Absolute path
        if (strpos($logPath, '/') === 0) {
            return $logPath;
        }

        // Remove 'tmp/' at the beginning
        if (strpos($logPath, 'tmp/') === 0) {
            $logPath = substr($logPath, strlen('tmp'));
        }

        if (empty($logPath)) {
            // Default log file
            $logPath = '/logs/piwik.log';
        }

        $logPath = $c->get('path.tmp') . $logPath;
        if (is_dir($logPath)) {
            $logPath .= '/piwik.log';
        }

        return $logPath;
    }),

    'log.syslog.ident' => Matomo\DI::factory(function (Container $c) {
        $ident = $c->get('ini.log.logger_syslog_ident');
        if (empty($ident)) {
            $ident = 'matomo';
        }
        return $ident;
    }),

    'Matomo\Plugins\Monolog\Formatter\LineMessageFormatter' => Matomo\DI::create('Matomo\Plugins\Monolog\Formatter\LineMessageFormatter')
                                                                ->constructor(Matomo\DI::get('log.short.format')),
    'log.lineMessageFormatter' => Matomo\DI::create('Matomo\Plugins\Monolog\Formatter\LineMessageFormatter')
        ->constructor(Matomo\DI::get('log.short.format')),

    'log.lineMessageFormatter.file' => Matomo\DI::autowire('Matomo\Plugins\Monolog\Formatter\LineMessageFormatter')
        ->constructor(Matomo\DI::get('log.trace.format'))
        ->constructorParameter('allowInlineLineBreaks', false),

    'log.short.format' => Matomo\DI::factory(function (Container $c) {
        if ($c->has('ini.log.string_message_format')) {
            return $c->get('ini.log.string_message_format');
        }
        return '%level% %tag%[%datetime%] %message%';
    }),

    'log.trace.format' => Matomo\DI::factory(function (Container $c) {
        if ($c->has('ini.log.string_message_format_trace')) {
            return $c->get('ini.log.string_message_format_trace');
        }
        return '%level% %tag%[%datetime%] %message% %trace%';
    }),

    'archiving.performance.handlers' => function (Container $c) {
        $logFile = trim($c->get('ini.Debug.archive_profiling_log'));
        if (empty($logFile)) {
            return [new \Monolog\Handler\NullHandler()];
        }

        $fileHandler = new FileHandler($logFile, Logger::INFO);
        $fileHandler->setFormatter($c->get('log.lineMessageFormatter.file'));
        return [$fileHandler];
    },

    'archiving.performance.logger' => Matomo\DI::create(Logger::class)
        ->constructor('matomo.archiving.performance', Matomo\DI::get('archiving.performance.handlers'), Matomo\DI::get('log.processors')),
);
