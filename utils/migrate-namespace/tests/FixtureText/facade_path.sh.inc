require_once PIWIK_INCLUDE_PATH . "/core/Piwik.php";

# shell snippet referencing the facade file after the rename
check() {
    if [ -f '/core/Piwik.php' ]; then
        echo "core/Piwik.php exists"
    fi
}
-----
require_once PIWIK_INCLUDE_PATH . "/core/Matomo.php";

# shell snippet referencing the facade file after the rename
check() {
    if [ -f '/core/Matomo.php' ]; then
        echo "core/Matomo.php exists"
    fi
}
