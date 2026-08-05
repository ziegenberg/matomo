<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals;

use Matomo\Matomo;

class TranslationHelper
{
    public function translateGoalMetricCategory($category)
    {
        // Return either "Goals by %s" or "Goals %s", depending on the category
        if ($category === 'General_Visit') {
                return Matomo::translate('Goals_GoalsAdjective', Matomo::translate('Goals_CategoryText' . $category));
        }
        return Matomo::translate('Goals_GoalsBy', Matomo::translate('Goals_CategoryText' . $category));
    }

    public function translateEcommerceMetricCategory($category)
    {
        // Return either "Sales by %s" or "Sales %s", depending on the category
        if ($category === 'General_Visit') {
                return Matomo::translate('Ecommerce_SalesAdjective', Matomo::translate('Goals_CategoryText' . $category));
        }
        return Matomo::translate('Ecommerce_SalesBy', Matomo::translate('Goals_CategoryText' . $category));
    }

    public function getTranslationForCompleteDescription($match, $patternType, $pattern)
    {
        $description = $this->getTranslationForMatchAttribute($match);
        if ($this->isPatternUsedForMatchAttribute($match)) {
            $description = sprintf(
                '%s %s',
                $description,
                $this->getTranslationForPattern(
                    $patternType,
                    $pattern
                )
            );
        }

        return $description;
    }

    protected function isPatternUsedForMatchAttribute($match)
    {
        return in_array(
            $match,
            array('url', 'title', 'event_category', 'event_action', 'event_name', 'file', 'external_website')
        );
    }

    protected function getTranslationForMatchAttribute($match)
    {
        switch ($match) {
            case 'manually':
                return Matomo::translate('Goals_ManuallyTriggeredUsingJavascriptFunction');

            case 'url':
                return Matomo::translate('Goals_VisitUrl');

            case 'title':
                return Matomo::translate('Goals_VisitPageTitle');

            case 'event_category':
            case 'event_action':
            case 'event_name':
                return Matomo::translate('Goals_SendEvent');

            case 'file':
                return Matomo::translate('Goals_Download');

            case 'external_website':
                return Matomo::translate('Goals_ClickOutlink');

            default:
                return '';
        }
    }

    protected function getTranslationForPattern($patternType, $pattern)
    {
        switch ($patternType) {
            case 'regex':
                return sprintf(
                    '%s %s',
                    Matomo::translate('Goals_Pattern'),
                    Matomo::translate('Goals_MatchesExpression', array($pattern))
                );

            case 'contains':
                return sprintf(
                    '%s %s',
                    Matomo::translate('Goals_Pattern'),
                    Matomo::translate('Goals_Contains', array($pattern))
                );

            case 'exact':
                return sprintf(
                    '%s %s',
                    Matomo::translate('Goals_Pattern'),
                    Matomo::translate('Goals_IsExactly', array($pattern))
                );

            default:
                return '';
        }
    }
}
