<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Feedback;

use Matomo\Common;
use Matomo\Config;
use Matomo\Config\GeneralConfig;
use Matomo\Container\StaticContainer;
use Matomo\DataTable\Renderer\Json;
use Matomo\Date;
use Matomo\Mail;
use Matomo\Matomo;
use Matomo\SettingsServer;
use Matomo\Url;
use Matomo\Version;

/**
 * Provides API methods for submitting product feedback and managing feedback reminders.
 *
 * @method static \Matomo\Plugins\Feedback\API getInstance()
 */
class API extends \Matomo\Plugin\API
{
    /**
     * Sends a survey response to the Matomo team or to the configured feedback email address.
     *
     * @param string $featureName Name of the feature the feedback is about.
     * @param bool|null $like Whether the user likes the feature.
     * @param string|null $choice Optional selected multiple-choice answer.
     * @param string|null $message Feedback message entered by the user.
     * @return string Translation key text when validation fails, or `success` when the feedback email is sent.
     */
    public function sendFeedbackForFeature(string $featureName, ?bool $like = null, ?string $choice = null, ?string $message = null)
    {
        Matomo::checkUserIsNotAnonymous();
        Matomo::checkUserHasSomeViewAccess();

        if (empty($message) || $message === 'undefined' ||  strlen($message) < 4) {
            return Matomo::translate("Feedback_FormNotEnoughFeedbackText");
        }

        $featureName = $this->getEnglishTranslationForFeatureName($featureName);

        $likeText = 'Yes';
        if (empty($like)) {
            $likeText = 'No';
        }

        $body = sprintf("Feature: %s\nLike: %s\n", $featureName, $likeText);

        if (!empty($choice) && $choice !== 'undefined') {
            $body .= "Choice: " . $choice . "\n";
        }

        $body .= sprintf("Feedback:\n%s\n", trim($message));

        $subject = sprintf(
            "%s for %s",
            empty($like) ? "-1" : "+1",
            $featureName
        );

        // Determine where Matomo is running and add as source
        if (Config::getHostname() === 'demo.matomo.cloud') {
            $source = 'Demo';
        } elseif (SettingsServer::isMatomoForWordPress()) {
            $source = 'Wordpress';
        } else {
            $source = 'On-Premise';
        }
        $body .= "Source: " . $source . "\n";

        $this->sendMail($subject, $body);

        return 'success';
    }

    /**
     * Sends feedback for a specific feature to the Matomo team or alternatively to the email address configured in the
     * config: "feedback_email_address".
     *
     * @param string $question Survey question or feature label the answer belongs to.
     * @param string|false $message Survey answer entered by the user.
     * @return string Translation key text when validation fails, or `success` when the feedback email is sent.
     */
    public function sendFeedbackForSurvey(string $question, $message = false): string
    {
        Matomo::checkUserIsNotAnonymous();
        Matomo::checkUserHasSomeViewAccess();

        if (empty($message) || strlen($message) < 10) {
            return Matomo::translate("Feedback_MessageBodyValidationError");
        }

        $featureName = $this->getEnglishTranslationForFeatureName($question);
        $body = sprintf("Question: %s\n", $featureName);
        $body .= sprintf("Answer:\n%s\n", trim($message));

        $subject = sprintf(
            "-1 for %s (w/ feedback Survey)",
            $featureName
        );

        $this->sendMail($subject, $body);

        //if feedback is sent set next one to 6 month.
        $nextReminder = Date::now()->getStartOfDay()->addMonth(6)->toString('Y-m-d');
        $feedbackReminder = new FeedbackReminder();
        $feedbackReminder->setUserOption($nextReminder);

        return 'success';
    }

    /**
     * Postpones the feedback reminder for the current user by six months.
     *
     * @return string JSON-encoded array containing the next reminder date.
     */
    public function updateFeedbackReminderDate(): string
    {
        Matomo::checkUserIsNotAnonymous();

        //push reminder for 6 month
        $nextReminder = Date::now()->getStartOfDay()->addMonth(6)->toString('Y-m-d');
        $feedbackReminder = new FeedbackReminder();
        $feedbackReminder->setUserOption($nextReminder);

        Json::sendHeaderJSON();
        return json_encode(['Next reminder date: ' . $nextReminder]) ?: '';
    }

    private function sendMail(string $subject, string $body): void
    {
        /** @var string $feedbackEmailAddress */
        $feedbackEmailAddress = GeneralConfig::getConfigValue('feedback_email_address');

        $subject = '[ Feedback Feature - Matomo ] ' . $subject;
        $body    = Common::unsanitizeInputValue($body) . "\n"
                 . 'Matomo ' . Version::VERSION . "\n"
                 . 'URL: ' . Url::getReferrer() . "\n";

        $mail = new Mail();
        $mail->setDefaultFromPiwik();
        $mail->addReplyTo(Matomo::getCurrentUserEmail());
        $mail->addTo($feedbackEmailAddress, 'Matomo Team');
        $mail->setSubject($subject);
        $mail->setBodyText($body);
        @$mail->send();
    }

    private function getEnglishTranslationForFeatureName(string $featureName): string
    {
        $translator = StaticContainer::get('Matomo\Translation\Translator');

        if ($translator->getCurrentLanguage() === 'en') {
            return $featureName;
        }

        $translationKeyForFeature = $translator->findTranslationKeyForTranslation($featureName);

        return Matomo::translate($translationKeyForFeature ?? '', [], 'en');
    }
}
