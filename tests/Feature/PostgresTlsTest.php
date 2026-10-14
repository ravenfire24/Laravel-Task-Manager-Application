<?php

namespace Tests\Feature;

use Illuminate\Database\ConfigurationUrlParser;
use Mockery;
use PDO;
use Tests\TestCase;

class PostgresTlsTest extends TestCase
{
    public function test_database_urls_cannot_downgrade_required_certificate_verification(): void
    {
        foreach (['disable', 'prefer', 'require'] as $mode) {
            $config = (new ConfigurationUrlParser)->parseConfiguration([
                'driver' => 'pgsql',
                'url' => 'postgres://fixture:fixture@database.invalid/tasks?sslmode='.$mode,
                'verify_server_certificate' => true,
                'sslrootcert' => '/trusted/public-ca.pem',
            ]);
            $connector = Mockery::mock(get_class($this->app['db.factory']->createConnector(['driver' => 'pgsql'])))->makePartial();
            $connector->shouldReceive('createConnection')->once()->withArgs(function ($dsn) {
                $this->assertStringContainsString(';sslmode=verify-full', $dsn);
                $this->assertStringContainsString(';sslrootcert=/trusted/public-ca.pem', $dsn);

                return true;
            })->andReturn(Mockery::mock(PDO::class));
            $connector->connect($config);
        }
    }

    public function test_local_postgres_can_keep_its_explicit_tls_setting(): void
    {
        $connector = Mockery::mock(get_class($this->app['db.factory']->createConnector(['driver' => 'pgsql'])))->makePartial();
        $connector->shouldReceive('createConnection')->once()->withArgs(function ($dsn) {
            $this->assertStringContainsString(';sslmode=disable', $dsn);

            return true;
        })->andReturn(Mockery::mock(PDO::class));
        $connector->connect([
            'database' => 'fixture', 'host' => 'localhost', 'sslmode' => 'disable',
            'verify_server_certificate' => false,
        ]);
    }
}
