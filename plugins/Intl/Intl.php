<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Intl;

class Intl extends \Matomo\Plugin
{
    public function registerEvents()
    {
        return [
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
        ];
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'Intl_PeriodDay';
        $translationKeys[] = 'Intl_PeriodMonth';
        $translationKeys[] = 'Intl_PeriodWeek';
        $translationKeys[] = 'Intl_PeriodYear';
        $translationKeys[] = 'CoreHome_PeriodRange';
    }
}
