<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

class DatabaseError
{
    public static function context(Throwable $exception): array
    {
        $state = $exception instanceof PDOException ? ($exception->errorInfo[0] ?? null) : null;
        $context = [
            'exception_type' => $exception::class,
            'sql_state' => is_string($state) && preg_match('/\A[A-Z0-9]{5}\z/', $state) ? $state : 'unknown',
        ];

        if ($exception instanceof QueryException) {
            $context['connection'] = $exception->getConnectionName();
            $context['query_hash'] = hash('sha256', $exception->getSql());
        }

        return $context;
    }
}
