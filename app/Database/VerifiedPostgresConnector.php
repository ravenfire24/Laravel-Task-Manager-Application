<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

class VerifiedPostgresConnector extends PostgresConnector
{
    protected function addSslOptions($dsn, array $config)
    {
        // Apply after URL parsing so provider URLs cannot downgrade verification.
        if ($config['verify_server_certificate'] ?? false) {
            $config['sslmode'] = 'verify-full';
        }

        return parent::addSslOptions($dsn, $config);
    }
}
