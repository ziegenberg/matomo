<?php

namespace Matomo\Tests\Framework\Mock\Tracker;

use Matomo\Tracker\Request;

class RequestAuthenticated extends Request
{
    protected $isAuthenticated = true;
}
