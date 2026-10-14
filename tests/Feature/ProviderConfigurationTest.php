<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProviderConfigurationTest extends TestCase
{
    #[DataProvider('endpointConfigurations')]
    public function test_endpoint_setup_respects_the_host_platform_and_existing_options(?string $vercel, mixed $url, ?string $options, ?string $expected): void
    {
        $environment = ['VERCEL' => getenv('VERCEL'), 'PGOPTIONS' => getenv('PGOPTIONS')];

        try {
            putenv($vercel === null ? 'VERCEL' : 'VERCEL='.$vercel);
            putenv($options === null ? 'PGOPTIONS' : 'PGOPTIONS='.$options);
            config(['database.connections.pgsql.url' => $url]);

            (new AppServiceProvider($this->app))->boot();

            $this->assertSame($expected ?? false, getenv('PGOPTIONS'));
        } finally {
            foreach ($environment as $name => $value) {
                putenv($value === false ? $name : $name.'='.$value);
            }
        }
    }

    public static function endpointConfigurations(): array
    {
        $pooled = 'postgresql://ep-fixture-pooler.neon.tech/tasks';
        $direct = 'postgresql://ep-fixture.neon.tech/tasks';

        return [
            'pooled endpoint' => ['1', $pooled, null, 'endpoint=ep-fixture'],
            'direct endpoint' => ['1', $direct, null, 'endpoint=ep-fixture'],
            'existing options' => ['1', $pooled, 'endpoint=operator-choice', 'endpoint=operator-choice'],
            'empty options' => ['1', $pooled, '', 'endpoint=ep-fixture'],
            'local platform' => [null, $pooled, null, null],
            'disabled Vercel flag' => ['0', $pooled, null, null],
            'nonmatching Vercel flag' => ['true', $pooled, null, null],
            'other database host' => ['1', 'postgresql://database.invalid/tasks', null, null],
            'unparseable host' => ['1', 'not a database URL', null, null],
            'missing URL' => ['1', null, null, null],
            'nonstring URL' => ['1', [], null, null],
        ];
    }
}
