<?php

return array(

    'Matomo\Plugins\CoreHome\Tracker\VisitRequestProcessor' => Matomo\DI::autowire()
        ->constructorParameter('visitStandardLength', Matomo\DI::get('ini.Tracker.visit_standard_length'))
        ->constructorParameter('trackerAlwaysNewVisitor', Matomo\DI::get('ini.Debug.tracker_always_new_visitor')),

);
