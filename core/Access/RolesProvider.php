<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Access;

use Matomo\Access\Role\Admin;
use Matomo\Access\Role\View;
use Matomo\Access\Role\Write;
use Matomo\Matomo;
use Exception;

class RolesProvider
{
    /**
     * @return Role[]
     */
    public function getAllRoles(): array
    {
        return array(
            new View(),
            new Write(),
            new Admin(),
        );
    }

    /**
     * Returns the list of the existing Access level.
     * Useful when a given API method requests a given access Level.
     * We first check that the required access level exists.
     *
     * @return string[]
     */
    public function getAllRoleIds(): array
    {
        $ids = array();
        foreach ($this->getAllRoles() as $role) {
            $ids[] = $role->getId();
        }
        return $ids;
    }

    public function isValidRole(string $roleId): bool
    {
        $roles = $this->getAllRoleIds();

        return \in_array($roleId, $roles, true);
    }

    /**
     * @throws Exception
     */
    public function checkValidRole(string $roleId): void
    {
        if (!$this->isValidRole($roleId)) {
            $roles = $this->getAllRoleIds();
            throw new Exception(Matomo::translate("UsersManager_ExceptionAccessValues", [implode(", ", $roles), $roleId]));
        }
    }
}
