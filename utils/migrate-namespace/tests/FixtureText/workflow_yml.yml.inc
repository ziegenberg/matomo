          OLD_VERSION=$(php -r "include_once 'core/Version.php'; echo \Piwik\Version::VERSION;")
          NEW_VERSION=$(php -r "include_once 'core/Version.php'; \$v = new \Piwik\Version(); echo \$v->nextPreviewVersion(\Piwik\Version::VERSION);")
-----
          OLD_VERSION=$(php -r "include_once 'core/Version.php'; echo \Matomo\Version::VERSION;")
          NEW_VERSION=$(php -r "include_once 'core/Version.php'; \$v = new \Matomo\Version(); echo \$v->nextPreviewVersion(\Matomo\Version::VERSION);")
