<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Access\Role;

use Matomo\Access\Role;
use Matomo\Matomo;
use Matomo\Url;

class Admin extends Role
{
    public const ID = 'admin';

    public function getName(): string
    {
        return Matomo::translate('UsersManager_PrivAdmin');
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function getDescription(): string
    {
        return Matomo::translate('UsersManager_PrivAdminDescription', [
            Matomo::translate('UsersManager_PrivWrite'),
        ]);
    }

    public function getHelpUrl(): string
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/faq/general/faq_69/');
    }
}
