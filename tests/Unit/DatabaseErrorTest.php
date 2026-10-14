<?php

namespace Tests\Unit;

use App\Support\DatabaseError;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseErrorTest extends TestCase
{
    public function test_non_database_exceptions_retain_their_type_without_their_message(): void
    {
        $context = DatabaseError::context(new RuntimeException('private diagnostic marker'));

        $this->assertSame(['exception_type' => RuntimeException::class, 'sql_state' => 'unknown'], $context);
    }

    #[DataProvider('validStates')]
    public function test_valid_driver_sqlstates_are_preserved_without_server_details(string $state): void
    {
        $exception = new PDOException('private diagnostic marker');
        $exception->errorInfo = [$state, 7, 'private server detail'];

        $this->assertSame(['exception_type' => PDOException::class, 'sql_state' => $state], DatabaseError::context($exception));
    }

    public static function validStates(): array
    {
        return ['integrity constraint' => ['23000'], 'unique violation' => ['23505'], 'connection failure' => ['08006'], 'missing table' => ['42P01']];
    }

    #[DataProvider('invalidStates')]
    public function test_malformed_sqlstates_cannot_inject_diagnostic_content(mixed $state): void
    {
        $exception = new PDOException('private diagnostic marker');
        $exception->errorInfo = [$state, 7, 'private server detail'];

        $this->assertSame(['exception_type' => PDOException::class, 'sql_state' => 'unknown'], DatabaseError::context($exception));
    }

    public static function invalidStates(): array
    {
        return [
            'missing' => [null], 'empty' => [''], 'too short' => ['2300'], 'too long' => ['230000'],
            'lowercase' => ['42p01'], 'newline' => ["23000\n"], 'embedded details' => ['23000 private marker'],
            'integer' => [23000], 'array' => [['23000']],
        ];
    }

    public function test_absent_driver_error_info_has_a_safe_fallback(): void
    {
        $this->assertSame(['exception_type' => PDOException::class, 'sql_state' => 'unknown'], DatabaseError::context(new PDOException));
    }

    public function test_query_fingerprints_group_the_same_statement_without_logging_bindings(): void
    {
        $driverError = new PDOException('private server detail');
        $driverError->errorInfo = ['23505', 7, 'private server detail'];
        $sql = 'insert into tasks (title) values (?)';
        $first = DatabaseError::context(new QueryException('pgsql', $sql, ['first private title'], $driverError));
        $second = DatabaseError::context(new QueryException('pgsql', $sql, ['second private title'], $driverError));
        $different = DatabaseError::context(new QueryException('pgsql', 'delete from tasks where id = ?', [7], $driverError));

        $this->assertSame($first, $second);
        $this->assertSame('pgsql', $first['connection']);
        $this->assertSame(QueryException::class, $first['exception_type']);
        $this->assertSame('23505', $first['sql_state']);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $first['query_hash']);
        $this->assertNotSame($first['query_hash'], $different['query_hash']);
        $this->assertSame(['exception_type', 'sql_state', 'connection', 'query_hash'], array_keys($first));
    }
}
