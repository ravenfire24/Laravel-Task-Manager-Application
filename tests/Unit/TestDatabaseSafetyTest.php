<?php

namespace Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

class TestDatabaseSafetyTest extends TestCase
{
    public function test_phpunit_overrides_inherited_database_settings(): void
    {
        if (getenv('DATABASE_ISOLATION_PROBE') === '1') {
            $this->assertSame('testing', getenv('APP_ENV'));
            $this->assertSame('sqlite', getenv('DB_CONNECTION'));
            $this->assertSame(':memory:', getenv('DB_DATABASE'));
            $this->assertSame('(null)', getenv('DB_URL'));
            $testCase = new class('unused') extends \Tests\TestCase {};
            try {
                $app = $testCase->createApplication();
                $this->assertSame('sqlite', $app['config']->get('database.default'));
                $this->assertSame(':memory:', $app['config']->get('database.connections.sqlite.database'));
                $this->assertNull($app['config']->get('database.connections.sqlite.url'));
            } finally {
                // Laravel installs handlers during bootstrap; this unit probe owns their cleanup.
                restore_error_handler();
                restore_exception_handler();
            }

            return;
        }

        // This child runs only a unit test: it cannot migrate or connect to a database.
        $process = new Process([
            PHP_BINARY, 'vendor/bin/phpunit', '--no-progress',
            '--filter', 'test_phpunit_overrides_inherited_database_settings', __FILE__,
        ], dirname(__DIR__, 2), [
            'DATABASE_ISOLATION_PROBE' => '1',
            'APP_ENV' => 'production',
            'DB_CONNECTION' => 'pgsql',
            'DB_DATABASE' => 'unsafe-inherited-database',
            'DB_URL' => 'postgresql://database.invalid/unsafe-inherited-database',
        ]);
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
    }

    #[DataProvider('databaseConfigurations')]
    public function test_database_guard_rejects_unsafe_configuration_before_test_setup(array $database, bool $safe): void
    {
        $app = new Application(dirname(__DIR__, 2));
        $app->instance('config', new Repository(['database' => $database]));
        $testCase = new class('unused') extends \Tests\TestCase
        {
            public function check(Application $app): void
            {
                $this->assertSafeTestDatabase($app);
            }
        };

        if (! $safe) {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Tests require an in-memory SQLite database with no database URL.');
        }

        $testCase->check($app);
        if ($safe) {
            $this->addToAssertionCount(1);
        }
    }

    public static function databaseConfigurations(): array
    {
        $safe = ['default' => 'sqlite', 'connections' => ['sqlite' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'url' => '',
        ]]];
        $postgres = $safe;
        $postgres['default'] = 'pgsql';
        $file = $safe;
        $file['connections']['sqlite']['database'] = '/not-a-test-database.sqlite';
        $url = $safe;
        $url['connections']['sqlite']['url'] = 'sqlite:///not-a-test-database.sqlite';
        $driver = $safe;
        $driver['connections']['sqlite']['driver'] = 'pgsql';

        return [
            'isolated SQLite' => [$safe, true],
            'non-SQLite default' => [$postgres, false],
            'persistent SQLite' => [$file, false],
            'URL override' => [$url, false],
            'driver override' => [$driver, false],
            'missing configuration' => [[], false],
        ];
    }
}
