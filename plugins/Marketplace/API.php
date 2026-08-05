<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Marketplace;

use Exception;
use Matomo\Matomo;
use Matomo\Plugin\Manager as PluginManager;
use Matomo\Plugins\Marketplace\Api\Client;
use Matomo\Plugins\Marketplace\Api\Service;
use Matomo\Plugins\Marketplace\Plugins\InvalidLicenses;
use Matomo\Plugins\Marketplace\PluginTrial\Service as PluginTrialService;
use Matomo\Plugins\UsersManager\SystemSettings;
use Matomo\Plugins\UsersManager\Validators\AllowedEmailDomain;
use Matomo\Plugins\UsersManager\Validators\Email;
use Matomo\Validators\Exception as ValidatorException;
use Matomo\Validators\NotEmpty;

/**
 * The Marketplace API lets you manage your license key so you can download & install in one-click <a target="_blank" rel="noreferrer" href="https://matomo.org/recommends/premium-plugins/">paid premium plugins</a> you have subscribed to.
 *
 * @method static \Matomo\Plugins\Marketplace\API getInstance()
 */
class API extends \Matomo\Plugin\API
{
    private Client $marketplaceClient;

    private Service $marketplaceService;

    private InvalidLicenses $expired;

    private PluginManager $pluginManager;

    private Environment $environment;

    private PluginTrialService $pluginTrialService;

    public function __construct(
        Service $service,
        Client $client,
        InvalidLicenses $expired,
        PluginManager $pluginManager,
        Environment $environment,
        PluginTrialService $pluginTrialService
    ) {
        $this->marketplaceService = $service;
        $this->marketplaceClient  = $client;
        $this->expired = $expired;
        $this->pluginManager = $pluginManager;
        $this->environment = $environment;
        $this->pluginTrialService = $pluginTrialService;
    }

    /**
     * @internal
     */
    public function createAccount(string $email): bool
    {
        Matomo::checkUserHasSuperUserAccess();

        $licenseKey = (new LicenseKey())->get();

        if (!empty($licenseKey)) {
            // not translated to allow special handling in frontend
            throw new Exception('Marketplace_CreateAccountErrorLicenseExists');
        }

        $notEmptyValidator = new NotEmpty();
        $notEmptyValidator->validate($email);

        try {
            $emailValidator = new Email();
            $emailValidator->validate($email);
        } catch (ValidatorException $e) {
            // rethrow with changed message
            throw new ValidatorException(
                Matomo::translate('Marketplace_CreateAccountErrorEmailInvalid', $email)
            );
        }

        // Ensure the provided email uses a domain that is allowed (if configured)
        $systemSettings = new SystemSettings();
        $allowedDomainsValidator = new AllowedEmailDomain($systemSettings);
        $allowedDomainsValidator->validate($email);

        try {
            /** @var array $result */
            $result = $this->marketplaceService->fetch(
                'createAccount',
                [],
                [
                    'email' => $email,
                ],
                true,
                false
            );
        } catch (Service\Exception $e) {
            // not translated to allow special handling in frontend
            throw new Exception('Marketplace_CreateAccountErrorAPI');
        }

        $this->marketplaceClient->clearAllCacheEntries();

        $licenseKey = trim($result['data']['license_key'] ?? '');
        $status = $result['status'];

        if (200 !== $status || empty($licenseKey)) {
            switch ($status) {
                case 400:
                    $message = Matomo::translate('Marketplace_CreateAccountErrorAPIEmailInvalid');
                    break;

                case 409:
                    $message = Matomo::translate('Marketplace_CreateAccountErrorAPIEmailExists', $email);
                    break;

                default:
                    // not translated to allow special handling in frontend
                    $message = 'Marketplace_CreateAccountErrorAPI';
                    break;
            }

            throw new Exception($message);
        }

        $this->setLicenseKey($licenseKey);

        return true;
    }

    /**
     * Deletes an existing license key if one is set.
     *
     * @return bool `true` after the stored license key has been removed.
     */
    public function deleteLicenseKey(): bool
    {
        Matomo::checkUserHasSuperUserAccess();

        $this->setLicenseKey(null);
        return true;
    }

    /**
     * @unsanitized
     * @internal
     */
    public function requestTrial(string $pluginName): bool
    {
        Matomo::checkUserIsNotAnonymous();

        if (Matomo::hasUserSuperUserAccess()) {
            throw new Exception('Cannot request trial as a super user');
        }

        if (!$this->pluginManager->isValidPluginName($pluginName)) {
            throw new Exception('Invalid plugin name given ' . $pluginName);
        }

        $pluginInfo = $this->marketplaceClient->getPluginInfo($pluginName);

        if (empty($pluginInfo['name'])) {
            throw new Exception('Unable to find plugin with given name: ' . $pluginName);
        }

        $this->pluginTrialService->request($pluginInfo['name'], $pluginInfo['displayName'] ?: $pluginInfo['name']);

        return true;
    }

    /**
     * @internal
     */
    public function startFreeTrial(string $pluginName): bool
    {
        Matomo::checkUserHasSuperUserAccess();

        if (!$this->pluginManager->isValidPluginName($pluginName)) {
            throw new Exception('Invalid plugin name given');
        }

        $licenseKey = (new LicenseKey())->get();

        $this->marketplaceService->authenticate($licenseKey);

        /** @var array $result */
        $result = $this->marketplaceService->fetch(
            'plugins/' . $pluginName . '/freeTrial',
            [
                'num_users' => $this->environment->getNumUsers(),
                'num_websites' => $this->environment->getNumWebsites(),
            ],
            [],
            true,
            false
        );

        $this->marketplaceClient->clearAllCacheEntries();

        if (
            201 !== $result['status']
            || !is_string($result['data'])
            || '' !== trim($result['data'])
        ) {
            // We expect an exact empty 201 response from this API
            // Anything different should be an error
            throw new Exception(Matomo::translate('Marketplace_TrialStartErrorAPI'));
        }

        return true;
    }

    /**
     * Saves the given license key in case the key is actually valid (exists on the Matomo Marketplace and is not
     * yet expired).
     *
     * @param string $licenseKey Marketplace license key to validate and store.
     * @return bool `true` after the license key has been validated and saved.
     */
    public function saveLicenseKey(string $licenseKey): bool
    {
        Matomo::checkUserHasSuperUserAccess();

        $licenseKey = trim($licenseKey);

        // we are currently using the Marketplace service directly to 1) change LicenseKey and 2) not use any cache
        $this->marketplaceService->authenticate($licenseKey);

        try {
            /** @var array $consumer */
            $consumer = $this->marketplaceService->fetch('consumer/validate', array());
        } catch (Api\Service\Exception $e) {
            if ($e->getCode() === Api\Service\Exception::HTTP_ERROR) {
                throw $e;
            }

            $consumer = [];
        }

        if (empty($consumer['isValid'])) {
            throw new Exception(Matomo::translate('Marketplace_ExceptionLinceseKeyIsNotValid'));
        }

        $this->setLicenseKey($licenseKey);

        return true;
    }

    private function setLicenseKey(?string $licenseKey): void
    {
        $key = new LicenseKey();
        $key->set($licenseKey);

        $this->marketplaceClient->clearAllCacheEntries();
        $this->expired->clearCache();
    }
}
