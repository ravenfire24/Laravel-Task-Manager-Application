<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaskBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_and_edit_shared_tasks_without_overwriting_server_managed_fields(): void
    {
        $this->freezeTime();
        $this->assertGuest();
        $injected = ['id' => 999, 'position' => -999, 'created_at' => '2000-01-01', 'updated_at' => '2000-01-01'];
        $this->postJson('/tasks', [...$injected, 'title' => 'Shared task', 'status' => 'pending'])->assertRedirect('/tasks');
        $task = Task::firstOrFail();
        $id = $task->id;
        $position = $task->position;
        $createdAt = $task->getRawOriginal('created_at');

        $this->assertNotSame(999, $id);
        $this->assertSame(-$id, $position);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $createdAt);
        $this->travel(1)->minutes();
        $this->putJson('/tasks/'.$id, [...$injected, 'title' => 'Changed task', 'description' => 'Allowed change', 'status' => 'completed'])
            ->assertRedirect('/tasks');

        $task->refresh();
        $this->assertSame($id, $task->id);
        $this->assertSame($position, $task->position);
        $this->assertSame($createdAt, $task->getRawOriginal('created_at'));
        $this->assertSame(now()->format('Y-m-d H:i:s'), $task->getRawOriginal('updated_at'));
        $this->assertSame('Changed task', $task->title);
        $this->assertSame('Allowed change', $task->description);
        $this->assertSame(TaskStatus::Completed, $task->status);
        $this->assertDatabaseCount('tasks', 1);
    }

    public function test_status_only_updates_are_repeatable_and_cannot_edit_other_task_details(): void
    {
        $task = Task::factory()->create(['title' => 'Original title', 'description' => 'Original description']);
        $position = $task->position;

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->patchJson('/tasks/'.$task->id.'/status', [
                'status' => 'completed', 'title' => 'Unexpected title', 'description' => null, 'id' => 999, 'position' => -999,
            ])->assertRedirect();
        }

        $task->refresh();
        $this->assertSame(TaskStatus::Completed, $task->status);
        $this->assertSame('Original title', $task->title);
        $this->assertSame('Original description', $task->description);
        $this->assertSame($position, $task->position);
        $this->assertDatabaseCount('tasks', 1);
    }

    #[DataProvider('invalidUpdates')]
    public function test_invalid_updates_preserve_the_entire_existing_record(array $changes, string $field): void
    {
        $task = Task::factory()->create(['title' => 'Original title', 'description' => 'Original description']);
        $original = $task->fresh()->getAttributes();

        $this->putJson('/tasks/'.$task->id, [...['title' => 'Changed title', 'status' => 'completed'], ...$changes])
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertSame($original, $task->fresh()->getAttributes());
        $this->assertDatabaseCount('tasks', 1);
    }

    public static function invalidUpdates(): array
    {
        return [
            'array title' => [['title' => ['unexpected']], 'title'],
            'array description' => [['description' => ['unexpected']], 'description'],
            'array status' => [['status' => ['completed']], 'status'],
            'oversized title' => [['title' => str_repeat('a', 256)], 'title'],
            'oversized description' => [['description' => str_repeat('x', 10001)], 'description'],
        ];
    }

    public function test_zero_search_text_is_applied_and_summary_counts_still_include_the_whole_list(): void
    {
        $matching = Task::factory()->create(['title' => 'Plan 0', 'status' => 'pending']);
        Task::factory()->create(['title' => 'Other task', 'status' => 'pending']);
        Task::factory()->create(['title' => 'Done 0', 'status' => 'completed']);

        $this->get('/tasks?search=0&status=pending')->assertOk()
            ->assertViewHas('tasks', fn ($page) => $page->pluck('id')->all() === [$matching->id])
            ->assertViewHas('counts', ['all' => 3, 'pending' => 2, 'completed' => 1]);
    }

    public function test_array_search_and_status_filters_are_rejected(): void
    {
        $this->getJson('/tasks?search[]=unexpected&status[]=pending')
            ->assertUnprocessable()->assertJsonValidationErrors(['search', 'status']);
    }
}
