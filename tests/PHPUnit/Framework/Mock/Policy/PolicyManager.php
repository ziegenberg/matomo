<?php

namespace Matomo\Tests\Framework\Mock\Policy;

use Matomo\Tests\Framework\Mock\Settings\FakePolicySetting;

class PolicyManager extends \Matomo\Policy\PolicyManager
{
    public static function getAllPolicies(): array
    {
        return [
            TestPolicy::class,
        ];
    }

    protected static function getAllSettings(?string $settingType = null): array
    {
        $settings[] = FakePolicySetting::class;
        return $settings;
    }
}
