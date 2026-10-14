<?php

use App\Support\DatabaseError;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

if (! $app['config']->get('app.key')) {
    fwrite(STDERR, "Set a stable APP_KEY in the Vercel project's environment variables before deploying.\n");
    exit(1);
}

if ($app['config']->get('database.default') !== 'pgsql' || ! $app['config']->get('database.connections.pgsql.url')) {
    fwrite(STDERR, "Connect a Postgres database and set DATABASE_URL (or DB_URL) in Vercel before deploying.\n");
    exit(1);
}

// Migrations need a direct connection; application requests keep using the pooled URL.
$directUrl = getenv('DATABASE_URL_UNPOOLED') ?: getenv('DB_MIGRATION_URL');
if (! $directUrl) {
    fwrite(STDERR, "Set DATABASE_URL_UNPOOLED (or DB_MIGRATION_URL) for migrations.\n");
    exit(1);
}
$app['config']->set('database.connections.pgsql.url', $directUrl);
$app['db']->purge('pgsql');

// Each environment must have its own database. Never point previews at production data.
try {
    $app['db']->connection()->getPdo();
    $status = $kernel->call('migrate', ['--force' => true]);
    if ($status !== 0) {
        fwrite(STDERR, "Migration command failed; see the structured database error log.\n");
        exit($status);
    }
    echo $kernel->output();
    if (! $app['db']->connection()->getSchemaBuilder()->hasTable('tasks')) {
        fwrite(STDERR, "Task schema is missing after migrations.\n");
        exit(1);
    }
    exit($status);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Database connection or migration failed: '.json_encode(DatabaseError::context($exception))."\n");
    exit(1);
}
