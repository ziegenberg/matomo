<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\MobileMessaging;

use Matomo\Common;
use Matomo\Http\JsonResponse;
use Matomo\Intl\Data\Provider\RegionDataProvider;
use Matomo\IP;
use Matomo\Matomo;
use Matomo\Plugin\ControllerAdmin;
use Matomo\Plugins\LanguagesManager\LanguagesManager;
use Matomo\Plugins\MobileMessaging\SMSProvider;
use Matomo\Translation\Translator;
use Matomo\View;

require_once PIWIK_INCLUDE_PATH . '/plugins/UserCountry/functions.php';

class Controller extends ControllerAdmin
{
    private RegionDataProvider $regionDataProvider;

    private Translator $translator;

    public function __construct(RegionDataProvider $regionDataProvider, Translator $translator)
    {
        $this->regionDataProvider = $regionDataProvider;
        $this->translator = $translator;

        parent::__construct();
    }

    /**
     * Mobile Messaging Settings tab :
     *  - set delegated management
     *  - provide & validate SMS API credential
     *  - add & activate phone numbers
     *  - check remaining credits
     */
    public function index()
    {
        Matomo::checkUserIsNotAnonymous();

        $view = new View('@MobileMessaging/index');
        $this->setManageVariables($view);

        return $view->render();
    }

    private function setManageVariables(View $view)
    {
        $view->isSuperUser = Matomo::hasUserSuperUserAccess();

        $mobileMessagingAPI = API::getInstance();
        $model = new Model();
        $view->delegatedManagement = $mobileMessagingAPI->getDelegatedManagement();
        $view->credentialSupplied = $mobileMessagingAPI->areSMSAPICredentialProvided();
        $view->accountManagedByCurrentUser = $view->isSuperUser || $view->delegatedManagement;
        $view->strHelpAddPhone = $this->translator->translate('MobileMessaging_Settings_PhoneNumbers_HelpAdd', array(
            $this->translator->translate('General_Settings'),
            $this->translator->translate('MobileMessaging_SettingsMenu'),
        ));
        $view->credentialError = null;
        $view->creditLeft = 0;
        $currentProvider = '';
        if ($view->credentialSupplied && $view->accountManagedByCurrentUser) {
            $currentProvider = $mobileMessagingAPI->getSMSProvider();
            try {
                $view->creditLeft = $mobileMessagingAPI->getCreditLeft();
            } catch (\Exception $e) {
                $view->credentialError = $e->getMessage();
            }
        }

        $view->delegateManagementOptions = array(
            array('key' => '0',
                  'value' => Matomo::translate('General_No'),
                  'description' => Matomo::translate('General_Default') . '. ' .
                                   Matomo::translate('MobileMessaging_Settings_LetUsersManageAPICredential_No_Help')),
            array('key' => '1',
                  'value' => Matomo::translate('General_Yes'),
                  'description' => Matomo::translate('MobileMessaging_Settings_LetUsersManageAPICredential_Yes_Help')),
        );

        $providers = array();
        $providerOptions = array();
        foreach (SMSProvider::findAvailableSmsProviders() as $provider) {
            if (empty($currentProvider)) {
                $currentProvider = $provider->getId();
            }
            $providers[$provider->getId()] = $provider->getDescription();
            $providerOptions[$provider->getId()] = $provider->getId();
        }

        $view->provider = $currentProvider;
        $view->smsProviders = $providers;
        $view->smsProviderOptions = $providerOptions;

        $defaultCountry = Common::getCountry(
            LanguagesManager::getLanguageCodeForCurrentUser(),
            true,
            IP::getIpFromHeader()
        );

        $view->defaultCallingCode = '';

        // construct the list of countries from the lang files
        $countries = array(array('key' => '', 'value' => ''));
        foreach ($this->regionDataProvider->getCountryList() as $countryCode => $continentCode) {
            if (isset(CountryCallingCodes::$countryCallingCodes[$countryCode])) {
                if ($countryCode == $defaultCountry) {
                    $view->defaultCallingCode = CountryCallingCodes::$countryCallingCodes[$countryCode];
                }

                $countries[] = array(
                    'key' => CountryCallingCodes::$countryCallingCodes[$countryCode],
                    'value' => \Matomo\Plugins\UserCountry\countryTranslate($countryCode),
                );
            }
        }
        $view->countries = $countries;

        $this->setBasicVariablesView($view);
    }

    #[JsonResponse]
    public function getCredentialFields(): string
    {
        $provider = Common::getRequestVar('provider', '');

        $credentialFields = array();

        foreach (SMSProvider::findAvailableSmsProviders() as $availableSmsProvider) {
            if ($availableSmsProvider->getId() == $provider) {
                $credentialFields = $availableSmsProvider->getCredentialFields();
                break;
            }
        }

        return json_encode($credentialFields);
    }
}
