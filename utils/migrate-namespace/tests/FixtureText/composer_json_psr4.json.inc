    "autoload": {
        "psr-4": {
            "Piwik\\Plugins\\": "plugins/",
            "Matomo\\Plugins\\": "plugins/",
            "Matomo\\": "core/"
        },
    "autoload-dev": {
        "psr-4": {
            "Piwik\\Tests\\": "tests/PHPUnit/",
            "Matomo\\Tests\\": "tests/PHPUnit/"
        }
-----
    "autoload": {
        "psr-4": {
            "Matomo\\Plugins\\": "plugins/",
            "Matomo\\Plugins\\": "plugins/",
            "Matomo\\": "core/"
        },
    "autoload-dev": {
        "psr-4": {
            "Matomo\\Tests\\": "tests/PHPUnit/",
            "Matomo\\Tests\\": "tests/PHPUnit/"
        }
