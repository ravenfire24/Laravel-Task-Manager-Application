<?php

namespace Tests\Feature;

use App\Http\Controllers\TaskOrderController;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Mockery;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TaskOrderFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_failure_after_the_first_position_write_rolls_back_the_entire_reorder(): void
    {
        $tasks = Task::factory()->count(3)->create();
        $original = Task::orderBy('id')->get()->map(fn (Task $task) => $task->getAttributes())->all();
        $transactionLevel = DB::connection()->transactionLevel();
        $dispatcher = Task::getEventDispatcher();
        Task::setEventDispatcher(clone $dispatcher);

        try {
            Task::updating(function (Task $task) use ($tasks): void {
                if ($task->id === $tasks[2]->id) {
                    // Prove the first write occurred before injecting the second-write failure.
                    $this->assertSame($tasks[2]->position, $tasks[0]->fresh()->position);
                    throw new RuntimeException('Simulated position write failure.');
                }
            });

            $this->withoutExceptionHandling();
            try {
                $this->patchJson('/tasks/order', ['ids' => [$tasks[0]->id, $tasks[2]->id, $tasks[1]->id]]);
                $this->fail('The write failure must propagate.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Simulated position write failure.', $exception->getMessage());
            }
        } finally {
            Task::setEventDispatcher($dispatcher);
        }

        $this->assertSame($original, Task::orderBy('id')->get()->map(fn (Task $task) => $task->getAttributes())->all());
        $this->assertSame($transactionLevel, DB::connection()->transactionLevel());
    }

    public function test_a_task_disappearing_after_validation_causes_a_conflict_without_changing_survivors(): void
    {
        $tasks = Task::factory()->count(3)->create();
        $survivorPositions = $tasks->take(2)->pluck('position', 'id')->all();
        $ids = $tasks->pluck('id')->all();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->andReturnUsing(function (array $rules) use ($ids, $tasks): array {
            $validated = Validator::make(['ids' => $ids], $rules)->validate();
            // Simulate deletion between successful validation and the locked lookup.
            $tasks[2]->delete();

            return $validated;
        });

        try {
            (new TaskOrderController)($request);
            $this->fail('A stale task list must not be reported as saved.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
            $this->assertSame('The list changed. Refresh and try again.', $exception->getMessage());
        }

        $this->assertDatabaseCount('tasks', 2);
        $this->assertSame($survivorPositions, Task::orderBy('id')->pluck('position', 'id')->all());
    }
}
