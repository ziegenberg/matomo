<?php

namespace Matomo\Plugins\CoreUpdater\ReleaseChannel;

use Matomo\UpdateCheck\ReleaseChannel;
use Matomo\Url;
use Matomo\Version;

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
        $majorVersion = (int) Version::VERSION;
        $majorVersion += 1;

        return 'http://' . Url::getHost(false) . '/tests/resources/one-click-update-version.php?v=' . $majorVersion;
    }

    public function getDownloadUrlWithoutScheme($version)
    {
        return '://' . Url::getHost(false) . '/matomo-build.zip';
    }
}
