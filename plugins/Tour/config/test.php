<?php

return array(
    'observers.global' => Matomo\DI::add(array(
        array('API.Tour.getChallenges.end', Matomo\DI::value(function (&$challenges) {
            $completeAllChanges = \Matomo\Container\StaticContainer::get('test.vars.completeAllChallenges');
            if ($completeAllChanges) {
                foreach ($challenges as &$challenge) {
                    $challenge['isSkipped'] = true;
                    $challenge['isCompleted'] = true;
                }
            }
            $completeNoChallenge = \Matomo\Container\StaticContainer::get('test.vars.completeNoChallenge');
            if ($completeNoChallenge) {
                foreach ($challenges as &$challenge) {
                    $challenge['isSkipped'] = false;
                    $challenge['isCompleted'] = false;
                }
            }
        })),
    )),
);
