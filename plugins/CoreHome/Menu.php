<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome;

use Matomo\Category\Category;
use Matomo\Category\CategoryList;
use Matomo\Menu\MenuTop;
use Matomo\Matomo;
use Matomo\Plugins\UsersManager\UserPreferences;
use Matomo\Request;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureTopMenu(MenuTop $menu)
    {
        $this->configureReportingGroupMenuItems($menu);

        $module = $this->getLoginModule();
        if (Matomo::isUserIsAnonymous()) {
            $menu->registerMenuIcon('Login_LogIn', 'icon-sign-in');
            $menu->addItem('Login_LogIn', null, array('module' => $module, 'action' => false), 1000, Matomo::translate('Login_LogIn'));
        } else {
            $menu->registerMenuIcon('General_Logout', 'icon-sign-out');
            $menu->addItem('General_Logout', null, array('module' => $module, 'action' => 'logout', 'idSite' => null), 1000, Matomo::translate('General_Logout'));
        }
    }

    /**
     * Adds a top menu entry for each non-default reporting menu group (e.g. "AI Insights"). Each entry
     * leads into the regular reporting single-page-app, which filters the reporting menu to the active
     * group. This keeps every reporting section within the same SPA (no full page reloads when navigating
     * within a section) and lets categories opt into sections via Category::setGroups() instead of any
     * plugin scraping another plugin's reports.
     *
     * The reporting SPA reads the section from the hash, filters the menu to it and selects its first
     * page. The `data-reporting-group` attribute lets the active top-menu highlight be kept in sync
     * client-side (the server cannot read the hash).
     */
    private function configureReportingGroupMenuItems(MenuTop $menu): void
    {
        $idSite = $this->getMenuIdSite();
        if (null === $idSite) {
            return;
        }

        foreach ($this->getReportingMenuGroups() as $group => $order) {
            $params = $this->urlForModuleActionWithDefaultUserParams('CoreHome', 'index', ['idSite' => $idSite]);
            if (empty($params)) {
                continue;
            }

            $url = $this->urlForReportingSection($params, $group);

            $menu->addItem($group, null, $url, $order, false, false, false, 'data-reporting-group="' . $group . '"');
        }
    }

    /**
     * Resolves the site id to use for the reporting group menu entries. Mirrors the Analytics top-menu
     * entry: the entry stays visible (linking to the user's default site) even on pages without an idSite
     * in the URL, such as the All Websites page or admin pages.
     */
    private function getMenuIdSite(): ?int
    {
        $userPreferences = new UserPreferences();
        $idSite = (int) $userPreferences->getDefaultWebsiteId();
        $idSite = Request::fromRequest()->getIntegerParameter('idSite', $idSite);

        if (!$idSite || !Matomo::isUserHasViewAccess($idSite)) {
            return null;
        }

        return $idSite;
    }

    /**
     * Returns the reporting menu groups (excluding the default Analytics group) that have at least one
     * category with reporting pages, mapped to the order of the resulting top-menu entry. Sections are
     * ordered by their lowest category order and placed right after the Analytics entry.
     *
     * @return array<string, int> map of group id => top-menu order
     */
    private function getReportingMenuGroups(): array
    {
        $categoryOrderByGroup = [];

        foreach (CategoryList::get()->getCategories() as $category) {
            if (!$category->hasSubCategories()) {
                continue;
            }

            foreach ($category->getGroups() as $group) {
                if (Category::DEFAULT_GROUP === $group) {
                    continue;
                }

                $order = (int) $category->getOrder();
                if (!isset($categoryOrderByGroup[$group]) || $order < $categoryOrderByGroup[$group]) {
                    $categoryOrderByGroup[$group] = $order;
                }
            }
        }

        asort($categoryOrderByGroup);

        $groups = [];
        $order = 2; // right after the Analytics entry (order 1)
        foreach (array_keys($categoryOrderByGroup) as $group) {
            $groups[$group] = $order++;
        }

        return $groups;
    }

    private function getLoginModule()
    {
        return Matomo::getLoginPluginName();
    }
}
