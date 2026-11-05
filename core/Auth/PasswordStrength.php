<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Auth;

use Matomo\Matomo;

/**
 * Main class to handle actions related to password strength rules and verification of
 * those rules.
 *
 * @api
 */
class PasswordStrength
{
    private bool $enabled;

    public function __construct(bool $featureEnabled)
    {
        $this->enabled = $featureEnabled;
    }

    /**
     * Provides the rules for defining a strong password. Rules are
     * broken up into a regular expression which is applied to a password candidate,
     * and a string which describes what the rule is testing for.
     *
     * @return array of rules to test password candidates against.
     */
    public function getRules(): array
    {
        if (!$this->enabled) {
            return [];
        }

        return [
            [
                'validationRegex' => '/^.{12,}$/',
                'ruleText' => Matomo::translate('General_PasswordStrengthValidationLength'),
            ],
            [
                'validationRegex' => '/^.*[a-z].*$/',
                'ruleText' => Matomo::translate('General_PasswordStrengthValidationLowercase'),
            ],
            [
                'validationRegex' => '/^.*[A-Z].*$/',
                'ruleText' => Matomo::translate('General_PasswordStrengthValidationUppercase'),
            ],
            [
                'validationRegex' => '/^.*[0-9].*$/',
                'ruleText' => Matomo::translate('General_PasswordStrengthValidationNumber'),
            ],
            [
                'validationRegex' => '/^.*[!\"#$%&\\\'(\\\\)*+,\-.\/:;<=>?@[\\]^_\`{\|}\~].*$/',
                'ruleText' => Matomo::translate('General_PasswordStrengthValidationSpecialChar'),
            ],
        ];
    }

    /**
     * Determines which rules a password candidate breaks with regards to
     * password strength.
     *
     * @param string $candidate The password candidate to be tested.
     * @return array of rules which the password breaks.
     */
    public function validatePasswordStrength(string $candidate): array
    {
        if (!$this->enabled) {
            return [];
        }

        $brokenRules = [];
        foreach ($this->getRules() as $rule) {
            if (!preg_match($rule['validationRegex'], $candidate)) {
                $brokenRules[] = $rule['ruleText'];
            }
        }

        return $brokenRules;
    }

    public function formatValidationFailedMessage(array $brokenRules): string
    {
        if (!$this->enabled || empty($brokenRules)) {
            return '';
        }

        $concatenatedRules = implode(', ', array_map('lcfirst', $brokenRules));

        return Matomo::translate('General_PasswordStrengthValidationFailed', $concatenatedRules);
    }

    public function getRulesAsHtmlList(): string
    {
        $list = '';
        $rules = $this->getRules();
        foreach ($rules as $rule) {
            $ruleText = $rule['ruleText'];
            $list .= "<li>$ruleText</li>";
        }

        return "<ul class='browser-default'>$list</ul>";
    }
}
