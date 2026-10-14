<?php

namespace Tests\Feature;

use App\Support\DatabaseError;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;
use Tests\TestCase;

class DatabaseLogSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_exception_messages_do_not_interpolate_task_content(): void
    {
        $exception = $this->failedInsert();
        $this->assertStringNotContainsString('sensitive-task-marker', $exception->getMessage());
        $this->assertStringContainsString('SQL:', $exception->getMessage());
    }

    public function test_database_failures_are_logged_without_bindings_or_raw_exception_objects(): void
    {
        $exception = $this->failedInsert();
        Log::spy();
        report($exception);

        Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context) {
            $this->assertStringNotContainsString('sensitive-task-marker', $message);
            $this->assertArrayNotHasKey('exception', $context);
            $this->assertSame(QueryException::class, $context['exception_type']);
            $this->assertSame('23000', $context['sql_state']);
            $this->assertNotEmpty($context['query_hash']);

            return true;
        });
    }

    public function test_server_supplied_error_details_are_not_in_safe_diagnostics(): void
    {
        $driverError = new PDOException('DETAIL: sensitive-server-marker');
        $driverError->errorInfo = ['23505', 7, 'sensitive-server-marker'];
        $exception = new QueryException('pgsql', 'insert into tasks (title) values (?)', ['sensitive-task-marker'], $driverError);
        $diagnostics = DatabaseError::context($exception);
        $this->assertStringNotContainsString('sensitive-server-marker', json_encode($diagnostics));
        $this->assertStringNotContainsString('sensitive-task-marker', json_encode($diagnostics));
        $this->assertSame('23505', $diagnostics['sql_state']);
        $this->assertSame(QueryException::class, $diagnostics['exception_type']);
    }

    private function failedInsert(): QueryException
    {
        try {
            DB::table('tasks')->insert(['title' => 'sensitive-task-marker', 'description' => 'sensitive-task-marker', 'status' => 'invalid']);
        } catch (QueryException $exception) {
            return $exception;
        }

        $this->fail('Expected the database status constraint to reject the insert.');
    }
}
