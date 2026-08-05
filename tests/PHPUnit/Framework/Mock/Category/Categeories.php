<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Framework\Mock\Category;

use Matomo\Category\Category;
use Matomo\Category\Subcategory;
use Matomo\Plugin;

/**
 * FakeCategories for UnitTests
 * @since 3.0.0
 */
class Categories extends Plugin\Categories
{
    private $categories;
    private $subcategories;

    /**
     * @param Category[] $categories
     */
    public function setCategories($categories)
    {
        $cats = array();

        foreach ($categories as $category) {
            $cats[$category->getId()] = $category;
        }

        $this->categories = $cats;
    }

    /**
     * @param Subcategory[] $subcategories
     */
    public function setSubcategories($subcategories)
    {
        $this->subcategories = $subcategories;
    }

    public function getAllCategories()
    {
        if ($this->categories) {
            return $this->categories;
        }

        return parent::getAllCategories();
    }

    public function getAllSubcategories()
    {
        if ($this->subcategories) {
            return $this->subcategories;
        }

        return parent::getAllSubcategories();
    }
}
