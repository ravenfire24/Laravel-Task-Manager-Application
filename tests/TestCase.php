<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        // Check bootstrapped configuration before RefreshDatabase can run migrations.
        $this->assertSafeTestDatabase($app);

        return $app;
    }

    protected function assertSafeTestDatabase(Application $app): void
    {
        $config = $app->make('config');
        if ($config->get('database.default') !== 'sqlite'
            || $config->get('database.connections.sqlite.driver') !== 'sqlite'
            || $config->get('database.connections.sqlite.database') !== ':memory:'
            || ! in_array($config->get('database.connections.sqlite.url'), [null, ''], true)) {
            throw new RuntimeException('Tests require an in-memory SQLite database with no database URL.');
        }
    }
}
