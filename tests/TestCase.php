<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxtServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        LlmsTxt::clearConfigure();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LlmsTxtServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('llms-txt.route_enabled', true);
        config()->set('llms-txt.cache_enabled', false);
        config()->set('llms-txt.disk', 'public');
    }
}
