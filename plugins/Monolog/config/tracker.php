<?php

use Matomo\Container\Container;

function isTrackerDebugEnabled(Container $c)
{
    $trackerDebug = $c->get("ini.Tracker.debug");
    return ($trackerDebug == 1 || !empty($GLOBALS['PIWIK_TRACKER_DEBUG']));
}

return array(

    'ini.log.log_writers' => Matomo\DI::decorate(function ($previous, Container $c) {
        if (
            isTrackerDebugEnabled($c)
            && \Matomo\Common::isPhpCliMode()
        ) {
            $previous[] = 'screen';
            $previous = array_unique($previous);
        }
        return $previous;
    }),

    'log.handler.classes' => Matomo\DI::decorate(function ($previous, Container $c) {
        if (
            isset($previous['screen'])
            && isTrackerDebugEnabled($c)
        ) {
            $previous['screen'] = 'Matomo\Plugins\Monolog\Handler\EchoHandler';
        } else {
            unset($previous['screen']);
        }

        return $previous;
    }),

    'log.level' => Matomo\DI::decorate(function ($previous, Container $c) {
        if (isTrackerDebugEnabled($c)) {
            return \Matomo\Log\Logger::DEBUG;
        }

        return $previous;
    }),

);
