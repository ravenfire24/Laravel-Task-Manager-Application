<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_list_and_create_form_are_available(): void
    {
        $this->get('/')->assertRedirect('/tasks');
        $this->get('/tasks')->assertOk()->assertSee('A fresh start');
        $this->get('/tasks/create')->assertOk()->assertSee('Create task');
    }

    public function test_user_can_create_view_edit_complete_reopen_and_delete_a_task(): void
    {
        $this->post('/tasks', ['title' => 'Review proposal', 'description' => 'Check scope', 'status' => 'pending'])
            ->assertRedirect('/tasks')->assertSessionHas('success');
        $task = Task::firstOrFail();
        $this->get('/tasks')->assertOk()->assertSee('Review proposal')->assertSee('Pending');
        $this->get('/tasks/'.$task->id)->assertOk()->assertSee('Check scope');
        $this->get('/tasks/'.$task->id.'/edit')->assertOk()->assertSee('Review proposal');
        $this->put('/tasks/'.$task->id, ['title' => 'Review revised proposal', 'description' => null, 'status' => 'completed'])
            ->assertRedirect('/tasks');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Review revised proposal', 'status' => 'completed', 'description' => null]);
        foreach (['pending', 'completed'] as $status) {
            $this->from('/tasks')->patch('/tasks/'.$task->id.'/status', ['status' => $status])->assertRedirect('/tasks');
            $this->assertSame($status, $task->fresh()->status->value);
        }
        $this->delete('/tasks/'.$task->id)->assertRedirect('/tasks')->assertSessionHas('success');
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_description_is_optional_and_title_is_trimmed(): void
    {
        $this->post('/tasks', ['title' => '  Write README  ', 'status' => 'pending'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', ['title' => 'Write README', 'description' => null]);
    }

    public function test_invalid_creation_is_rejected_and_old_input_is_preserved(): void
    {
        $this->from('/tasks/create')->post('/tasks', ['title' => '  ', 'status' => 'unknown', 'description' => 'Keep these details'])
            ->assertRedirect('/tasks/create')->assertSessionHasErrors(['title', 'status'])
            ->assertSessionHasInput('description', 'Keep these details');
        $this->assertDatabaseCount('tasks', 0);
        $this->post('/tasks', ['title' => str_repeat('a', 256), 'description' => str_repeat('x', 10001)])
            ->assertSessionHasErrors(['title', 'description', 'status']);
    }

    public function test_invalid_edit_and_status_update_leave_task_unchanged(): void
    {
        $task = Task::factory()->create(['title' => 'Original']);
        $this->put('/tasks/'.$task->id, ['title' => '', 'status' => 'invalid'])->assertSessionHasErrors(['title', 'status']);
        $this->patch('/tasks/'.$task->id.'/status', ['status' => 'invalid'])->assertSessionHasErrors('status');
        $this->patch('/tasks/'.$task->id.'/status', [])->assertSessionHasErrors('status');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Original', 'status' => 'pending']);
    }

    public function test_search_and_status_filter_can_be_combined(): void
    {
        Task::factory()->create(['title' => 'Release checklist', 'status' => TaskStatus::Completed]);
        Task::factory()->create(['title' => 'Release notes', 'status' => TaskStatus::Pending]);
        Task::factory()->create(['title' => 'Team meeting', 'status' => TaskStatus::Pending]);
        $this->get('/tasks?search=Release&status=pending')->assertOk()
            ->assertSee('Release notes')->assertDontSee('Release checklist')->assertDontSee('Team meeting');
        $this->get('/tasks?search=unmatched')->assertOk()->assertSee('No matching tasks');
        $this->get('/tasks?status=invalid')->assertSessionHasErrors('status');
    }

    public function test_pagination_keeps_filters_and_orders_newest_first(): void
    {
        Task::factory()->count(11)->create(['title' => 'Release task']);
        $this->get('/tasks?search=Release&status=pending')->assertOk()->assertViewHas('tasks', function ($tasks) {
            return $tasks->count() === 10 && $tasks->total() === 11
                && $tasks->first()->id === 11
                && str_contains($tasks->url(2), 'search=Release')
                && str_contains($tasks->url(2), 'status=pending');
        });
        $this->get('/tasks?search=Release&status=pending&page=2')->assertOk()->assertViewHas('tasks', fn ($tasks) => $tasks->count() === 1);
    }

    public function test_task_content_is_escaped_in_views_and_delete_modal_attributes(): void
    {
        $task = Task::factory()->create(['title' => '<script>alert("x")</script>', 'description' => '<b>unsafe</b>']);
        $this->get('/tasks')->assertOk()->assertDontSee($task->title, false)->assertSee($task->title);
        $this->get('/tasks/'.$task->id)->assertOk()->assertDontSee('<b>unsafe</b>', false)->assertSee('<b>unsafe</b>');
    }

    public function test_missing_tasks_return_a_friendly_not_found_page(): void
    {
        $this->get('/tasks/999')->assertNotFound()->assertSee('Task not found');
        $this->get('/tasks/999/edit')->assertNotFound();
        $this->put('/tasks/999', ['title' => 'Missing', 'status' => 'pending'])->assertNotFound();
        $this->patch('/tasks/999/status', ['status' => 'pending'])->assertNotFound();
        $this->delete('/tasks/999')->assertNotFound();
    }

    public function test_delete_confirmation_has_cancel_and_delete_actions(): void
    {
        Task::factory()->create(['title' => 'Remove this task']);
        $this->get('/tasks')->assertOk()->assertSee('deleteTaskModal')->assertSee('Keep task')->assertSee('Delete task');
    }

    public function test_task_order_is_saved_and_used_by_the_list(): void
    {
        $tasks = Task::factory()->count(3)->create();
        $ids = $tasks->pluck('id')->all();
        $this->patchJson('/tasks/order', ['ids' => $ids])->assertOk()->assertJson(['message' => 'Task order saved.']);
        $this->get('/tasks')->assertViewHas('tasks', fn ($page) => $page->pluck('id')->all() === $ids);
        $this->get('/tasks')->assertViewHas('tasks', fn ($page) => $page->pluck('id')->all() === $ids);
    }

    public function test_reordering_a_filtered_subset_keeps_hidden_tasks_in_their_slots(): void
    {
        $first = Task::factory()->create(['status' => TaskStatus::Pending]);
        $hidden = Task::factory()->create(['status' => TaskStatus::Completed]);
        $last = Task::factory()->create(['status' => TaskStatus::Pending]);
        $hiddenPosition = $hidden->position;
        $this->patchJson('/tasks/order', ['ids' => [$first->id, $last->id]])->assertOk();
        $this->assertSame($hiddenPosition, $hidden->fresh()->position);
        $this->get('/tasks')->assertViewHas('tasks', fn ($page) => $page->pluck('id')->all() === [$first->id, $hidden->id, $last->id]);
        $this->get('/tasks?status=pending')->assertViewHas('tasks', fn ($page) => $page->pluck('id')->all() === [$first->id, $last->id]);
    }

    public function test_reordering_one_page_does_not_move_other_pages(): void
    {
        Task::factory()->count(12)->create();
        $this->patchJson('/tasks/order', ['ids' => range(3, 12)])->assertOk();
        $this->get('/tasks')->assertViewHas('tasks', fn ($page) => $page->pluck('id')->all() === range(3, 12));
        $this->get('/tasks?page=2')->assertViewHas('tasks', fn ($page) => $page->pluck('id')->all() === [2, 1]);
    }

    public function test_invalid_order_requests_do_not_change_positions(): void
    {
        $tasks = Task::factory()->count(11)->create();
        $positions = Task::orderBy('id')->pluck('position')->all();
        foreach ([[], [$tasks[0]->id], [$tasks[0]->id, $tasks[0]->id], [$tasks[0]->id, 999], ['bad', $tasks[0]->id], $tasks->pluck('id')->all()] as $ids) {
            $this->patchJson('/tasks/order', ['ids' => $ids])->assertUnprocessable();
            $this->assertSame($positions, Task::orderBy('id')->pluck('position')->all());
        }
        $this->patchJson('/tasks/order', ['ids' => ['first' => 1, 'second' => 2]])->assertUnprocessable();
    }

    public function test_new_tasks_start_at_the_top_and_status_changes_keep_saved_order(): void
    {
        $tasks = Task::factory()->count(2)->create();
        $this->patchJson('/tasks/order', ['ids' => $tasks->pluck('id')->all()])->assertOk();
        $this->patch('/tasks/'.$tasks[0]->id.'/status', ['status' => 'completed'])->assertSessionHasNoErrors();
        $new = Task::factory()->create();
        $this->get('/tasks')->assertViewHas('tasks', fn ($page) => $page->pluck('id')->all() === [$new->id, $tasks[0]->id, $tasks[1]->id]);
    }
}
