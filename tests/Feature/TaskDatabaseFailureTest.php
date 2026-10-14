<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\EnforcesCsrf;
use Tests\TestCase;

class TaskDatabaseFailureTest extends TestCase
{
    use EnforcesCsrf, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);
        $this->enableCsrfProtection();
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        $this->assertAuthenticatedAs($user, 'web');
        Log::spy();
    }

    #[DataProvider('failedWrites')]
    public function test_database_write_failures_return_safe_errors_and_preserve_task_data(string $method, string $suffix, string $event): void
    {
        $tasks = Task::factory()->count(2)->create();
        $original = $this->taskSnapshot();
        $this->rejectWrites($event);
        $uri = $method === 'POST' ? '/tasks' : '/tasks/'.$tasks[0]->id.$suffix;

        $this->json($method, $uri, ['title' => 'private-task-marker', 'description' => 'private-task-marker', 'status' => 'completed'])
            ->assertStatus(500)->assertExactJson(['message' => 'Server Error'])->assertSessionMissing('success');

        $this->assertSame($original, $this->taskSnapshot());
        $this->assertSafeFailureWasLogged();
    }

    public static function failedWrites(): array
    {
        return [
            'creation' => ['POST', '', 'INSERT'],
            'editing' => ['PUT', '', 'UPDATE'],
            'status change' => ['PATCH', '/status', 'UPDATE'],
            'deletion' => ['DELETE', '', 'DELETE'],
        ];
    }

    public function test_failed_initial_position_assignment_does_not_leave_a_partially_created_task(): void
    {
        Task::factory()->count(2)->create();
        $original = $this->taskSnapshot();
        $this->rejectWrites('UPDATE');

        $this->postJson('/tasks', ['title' => 'private-task-marker', 'status' => 'pending'])
            ->assertStatus(500)->assertExactJson(['message' => 'Server Error'])->assertSessionMissing('success');

        $this->assertDatabaseCount('tasks', 2);
        $this->assertSame($original, $this->taskSnapshot());
        $this->assertSafeFailureWasLogged();
    }

    public function test_a_database_failure_during_reorder_rolls_back_prior_position_writes(): void
    {
        $tasks = Task::factory()->count(3)->create();
        $original = $this->taskSnapshot();
        $secondWriteId = (int) $tasks[2]->id;
        DB::unprepared("CREATE TEMP TRIGGER reject_second_position BEFORE UPDATE ON tasks
            WHEN OLD.id = {$secondWriteId}
            BEGIN SELECT RAISE(ABORT, 'private-database-marker'); END");

        $this->patchJson('/tasks/order', ['ids' => [$tasks[0]->id, $tasks[2]->id, $tasks[1]->id]])
            ->assertStatus(500)->assertExactJson(['message' => 'Server Error'])->assertSessionMissing('success');

        $this->assertSame($original, $this->taskSnapshot());
        $this->assertSafeFailureWasLogged();
    }

    public function test_database_read_failure_returns_a_friendly_html_error_without_sql_details(): void
    {
        DB::unprepared('DROP TABLE tasks');

        $this->get('/tasks')->assertStatus(500)->assertSee('Something went wrong')
            ->assertDontSee('SQLSTATE', false)->assertDontSee('select count', false)->assertDontSee('QueryException', false);

        $this->assertSafeFailureWasLogged();
    }

    private function rejectWrites(string $event): void
    {
        // The fixed event values come from this test, never from request input.
        // SQLite triggers exercise real PDO failures inside the in-memory test database.
        DB::unprepared("CREATE TEMP TRIGGER reject_task_write BEFORE {$event} ON tasks
            BEGIN SELECT RAISE(ABORT, 'private-database-marker'); END");
    }

    private function taskSnapshot(): array
    {
        return Task::orderBy('id')->get()->map(fn (Task $task) => $task->getAttributes())->all();
    }

    private function assertSafeFailureWasLogged(): void
    {
        Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context): bool {
            $this->assertSame('Database operation failed.', $message);
            $this->assertSame(QueryException::class, $context['exception_type']);
            $this->assertMatchesRegularExpression('/\A[A-Z0-9]{5}\z/', $context['sql_state']);
            $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $context['query_hash']);
            $this->assertArrayNotHasKey('exception', $context);
            $this->assertStringNotContainsString('private-task-marker', json_encode($context));
            $this->assertStringNotContainsString('private-database-marker', json_encode($context));

            return true;
        });
    }
}
