<?php


namespace Piwik\Plugins\CoreUpdater\ReleaseChannel;

use Piwik\UpdateCheck\ReleaseChannel;
use Piwik\Url;
use Piwik\Version;

class GitCommitReleaseChannel extends ReleaseChannel
{
    public function getId()
    {
        return 'git_commit';
    }

    public function getName()
    {
        return 'Test Release Channel';
    }

    public function getUrlToCheckForLatestAvailableVersion()
    {
        return 'http://' . Url::getHost(false) . '/tests/resources/one-click-update-version.php?v=' . Version::MAJOR_VERSION;
    }

    public function getDownloadUrlWithoutScheme($version)
    {
        return '://' . Url::getHost(false) . '/matomo-build.zip';
    }
}
