<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Tour;

use Matomo\Common;
use Matomo\Container\StaticContainer;
use Matomo\Matomo;
use Matomo\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Matomo\Plugins\CoreVisualizations\Visualizations\Sparkline;
use Matomo\Plugins\Tour\Engagement\Challenge;
use Matomo\Plugins\Tour\Engagement\ChallengeAddedAnnotation;
use Matomo\Plugins\Tour\Engagement\ChallengeInvitedUser;
use Matomo\Plugins\Tour\Engagement\ChallengeBrowseMarketplace;
use Matomo\Plugins\Tour\Engagement\ChallengeChangeVisualisation;
use Matomo\Plugins\Tour\Engagement\ChallengeCreatedGoal;
use Matomo\Plugins\Tour\Engagement\ChallengeFlattenActions;
use Matomo\Plugins\Tour\Engagement\ChallengeSelectDateRange;
use Matomo\Plugins\Tour\Engagement\ChallengeViewRowEvolution;
use Matomo\Plugins\Tour\Engagement\ChallengeViewVisitorProfile;
use Matomo\Plugins\Tour\Engagement\ChallengeViewVisitsLog;

class Tour extends \Matomo\Plugin
{
    public function registerEvents()
    {
        return array(
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'Dashboard.changeDefaultDashboardLayout' => 'changeDefaultDashboardLayout',
            'API.Annotations.add.end' => 'onAnnotationAdded',
            'API.Goals.addGoal.end' => 'onGoalAdded',
            'UsersManager.inviteUser.end' => 'onUserInvited',
            'Controller.CoreHome.getRowEvolutionPopover' => 'onViewRowEvolution',
            'Controller.Live.getLastVisitsDetails' => 'onViewVisitorLog',
            'Controller.Live.getVisitorProfilePopup' => 'onViewVisitorProfile',
            'Controller.Marketplace.overview' => 'onBrowseMarketplace',
            'ViewDataTable.configure' => array('function' => 'onConfigureView', 'after' => true),
        );
    }

    public function onBrowseMarketplace()
    {
        $this->setSimpleChallengeCompleted(ChallengeBrowseMarketplace::class);
    }

    public function onConfigureView()
    {
        if (Common::getRequestVar('period', '', 'string') === 'range') {
            $this->setSimpleChallengeCompleted(ChallengeSelectDateRange::class);
        }

        if (Common::getRequestVar('flat', '0', 'string') === '1') {
            $module = Matomo::getModule();
            if ($module === 'Actions' || $module === 'Contents' || $module === 'UsersFlow') {
                $this->setSimpleChallengeCompleted(ChallengeFlattenActions::class);
            }
        }

        $viewDataTable = Common::getRequestVar('viewDataTable', '', 'string');
        if ($viewDataTable && !Common::getRequestVar('forceView', '', 'string')) {
            if ($viewDataTable !== Sparkline::ID && $viewDataTable !== Evolution::ID) {
                // sparkline and graphEvolution may be used without forceView
                $this->setSimpleChallengeCompleted(ChallengeChangeVisualisation::class);
            }
        }
    }

    private function setSimpleChallengeCompleted($className)
    {
        if (Matomo::hasUserSuperUserAccess()) {
            /** @var Challenge $challenge */
            $challenge = StaticContainer::get($className);
            $challenge->setCompleted(Matomo::getCurrentUserLogin());
        }
    }

    public function onViewRowEvolution()
    {
        $this->setSimpleChallengeCompleted(ChallengeViewRowEvolution::class);
    }

    public function onViewVisitorLog()
    {
        $this->setSimpleChallengeCompleted(ChallengeViewVisitsLog::class);
    }

    public function onViewVisitorProfile()
    {
        $this->setSimpleChallengeCompleted(ChallengeViewVisitorProfile::class);
    }

    public function onAnnotationAdded($response)
    {
        if (Matomo::hasUserSuperUserAccess() && !empty($response)) {
            $annotation = new ChallengeAddedAnnotation();
            $annotation->setCompleted(Matomo::getCurrentUserLogin());
        }
    }

    public function onGoalAdded($response)
    {
        if (Matomo::hasUserSuperUserAccess() && !empty($response)) {
            $annotation = new ChallengeCreatedGoal();
            $annotation->setCompleted(Matomo::getCurrentUserLogin());
        }
    }

    public function onUserInvited()
    {
        if (Matomo::hasUserSuperUserAccess()) {
            $annotation = new ChallengeInvitedUser();
            $annotation->setCompleted(Matomo::getCurrentUserLogin());
        }
    }

    public function changeDefaultDashboardLayout(&$defaultLayout)
    {
        if (Matomo::hasUserSuperUserAccess()) {
            $defaultLayout = json_decode($defaultLayout, true);
            $engagementWidget = array('uniqueId' => 'widgetTourgetEngagement', 'parameters' => array('module' => 'Tour', 'action' => 'getEngagement'));
            if (is_array($defaultLayout) && isset($defaultLayout[2]) && is_array($defaultLayout[2])) {
                array_unshift($defaultLayout[2], $engagementWidget);
            }
            $defaultLayout = json_encode($defaultLayout);
        }
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/Tour/stylesheets/engagement.less";
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'Tour_CompletionTitle';
        $translationKeys[] = 'Tour_CompletionMessage';
        $translationKeys[] = 'Tour_YouCanCallYourselfExpert';
        $translationKeys[] = 'Tour_ShareYourAchievementOn';
        $translationKeys[] = 'Tour_ShareAllChallengesCompleted';
        $translationKeys[] = 'Tour_StatusLevel';
        $translationKeys[] = 'Tour_ChallengeCompleted';
        $translationKeys[] = 'Tour_SkipThisChallenge';
        $translationKeys[] = 'Tour_PreviousChallenges';
        $translationKeys[] = 'Tour_NextChallenges';
        $translationKeys[] = 'Tour_OnlyVisibleToSuperUser';
        $translationKeys[] = 'General_Previous';
        $translationKeys[] = 'General_Next';
    }
}
