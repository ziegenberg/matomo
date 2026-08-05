<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Measurable\Type;

use Matomo\Container\StaticContainer;
use Matomo\Plugin\Manager as PluginManager;
use Matomo\Measurable\Type;

class TypeManager
{
    /**
     * @return Type[]
     */
    public function getAllTypes()
    {
        $components = PluginManager::getInstance()->findComponents('Type', '\Matomo\Measurable\Type');

        $instances = array();
        foreach ($components as $component) {
            $instances[] = StaticContainer::get($component);
        }

        return $instances;
    }

    public function isExistingType($typeId)
    {
        foreach ($this->getAllTypes() as $type) {
            if ($type->getId() === $typeId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $typeId
     * @return Type|null
     */
    public function getType($typeId)
    {
        foreach ($this->getAllTypes() as $type) {
            if ($type->getId() === $typeId) {
                return $type;
            }
        }

        return new Type();
    }
}
