<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\EnforcesCsrf;
use Tests\TestCase;

class TaskWorkflowTest extends TestCase
{
    use EnforcesCsrf, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);
        $this->enableCsrfProtection();
    }

    #[DataProvider('identities')]
    public function test_complete_shared_task_workflow_persists_changes_for_each_access_identity(bool $authenticated): void
    {
        $this->useIdentity($authenticated);
        $this->get('/tasks')->assertOk();
        $this->get('/tasks/create')->assertOk();
        $this->postJson('/tasks', ['title' => '  Review proposal  ', 'description' => 'Check scope', 'status' => 'pending'])
            ->assertRedirect('/tasks')->assertSessionHas('success', 'Task created.');
        $task = Task::firstOrFail();
        $this->assertSame('Review proposal', $task->title);
        $this->assertSame(-$task->id, $task->position);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'description' => 'Check scope', 'status' => 'pending']);
        $this->get('/tasks/'.$task->id)->assertOk()->assertSee('Check scope');
        $this->get('/tasks/'.$task->id.'/edit')->assertOk()->assertSee('Review proposal');

        $this->postJson('/tasks', ['title' => 'Second task', 'status' => 'pending'])->assertRedirect('/tasks');
        $second = Task::where('title', 'Second task')->firstOrFail();
        $this->putJson('/tasks/'.$task->id, ['title' => 'Revised proposal', 'description' => null, 'status' => 'completed'])
            ->assertRedirect('/tasks')->assertSessionHas('success', 'Task updated.');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Revised proposal', 'description' => null, 'status' => 'completed']);
        foreach (['pending', 'completed'] as $status) {
            $this->from('/tasks')->patchJson('/tasks/'.$task->id.'/status', ['status' => $status])
                ->assertRedirect('/tasks')->assertSessionHas('success', 'Task status updated.');
            $this->assertSame(TaskStatus::from($status), $task->fresh()->status);
        }

        $this->patchJson('/tasks/order', ['ids' => [$task->id, $second->id]])
            ->assertOk()->assertExactJson(['message' => 'Task order saved.']);
        $this->assertSame([$task->id, $second->id], Task::orderBy('position')->orderByDesc('id')->pluck('id')->all());
        $this->get('/tasks')->assertOk()->assertViewHas('tasks', fn ($page) => $page->pluck('id')->all() === [$task->id, $second->id]);

        $this->deleteJson('/tasks/'.$task->id)->assertRedirect('/tasks')->assertSessionHas('success', 'Task deleted.');
        $this->getJson('/tasks/'.$task->id)->assertNotFound();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseHas('tasks', ['id' => $second->id, 'title' => 'Second task']);
        $this->assertDatabaseCount('tasks', 1);
    }

    #[DataProvider('identities')]
    public function test_invalid_json_writes_and_filters_do_not_change_existing_data(bool $authenticated): void
    {
        $this->useIdentity($authenticated);
        $task = Task::factory()->create();
        $original = $task->fresh()->getAttributes();
        $invalid = ['title' => '', 'description' => ['unexpected'], 'status' => 'archived'];

        $this->postJson('/tasks', $invalid)->assertUnprocessable()->assertJsonValidationErrors(['title', 'description', 'status']);
        $this->putJson('/tasks/'.$task->id, $invalid)->assertUnprocessable()->assertJsonValidationErrors(['title', 'description', 'status']);
        $this->patchJson('/tasks/'.$task->id.'/status', ['status' => 'archived'])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->patchJson('/tasks/order', ['ids' => [$task->id, $task->id]])->assertUnprocessable()->assertJsonValidationErrors('ids.0');
        $this->getJson('/tasks?status=archived')->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->assertSame($original, $task->fresh()->getAttributes());
        $this->assertDatabaseCount('tasks', 1);
    }

    #[DataProvider('missingResources')]
    public function test_missing_resources_are_reported_without_mutating_other_tasks(bool $authenticated, string $method, string $suffix, bool $ordering): void
    {
        $this->useIdentity($authenticated);
        $task = Task::factory()->create();
        $original = $task->fresh()->getAttributes();
        $missingId = $task->id + 1000;
        $uri = $ordering ? '/tasks/order' : '/tasks/'.$missingId.$suffix;
        $payload = $ordering ? ['ids' => [$task->id, $missingId]] : ['title' => 'Missing task', 'status' => 'completed'];

        $response = $this->json($method, $uri, $payload);
        if ($ordering) {
            $response->assertUnprocessable()->assertJsonValidationErrors('ids.1');
        } else {
            $response->assertNotFound()->assertJsonStructure(['message']);
        }
        $response->assertJsonMissingPath('trace')->assertJsonMissingPath('exception');
        $this->assertSame($original, $task->fresh()->getAttributes());
        $this->assertDatabaseCount('tasks', 1);
    }

    public static function identities(): array
    {
        return ['guest' => [false], 'authenticated' => [true]];
    }

    public static function missingResources(): array
    {
        $cases = [];
        foreach (self::identities() as $identity => [$authenticated]) {
            foreach ([['GET', '', false], ['GET', '/edit', false], ['PUT', '', false], ['PATCH', '/status', false], ['DELETE', '', false], ['PATCH', '', true]] as [$method, $suffix, $ordering]) {
                $cases[$identity.' '.$method.' '.($ordering ? 'order' : 'task'.$suffix)] = [$authenticated, $method, $suffix, $ordering];
            }
        }

        return $cases;
    }

    public function test_signed_in_users_can_edit_and_delete_each_others_tasks_in_the_shared_demo(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->actingAs($first, 'web')->postJson('/tasks', ['title' => 'Shared task', 'status' => 'pending'])
            ->assertRedirect('/tasks');
        $task = Task::firstOrFail();

        $this->actingAs($second, 'web');
        $this->assertAuthenticatedAs($second, 'web');
        $this->get('/tasks/'.$task->id)->assertOk();
        $this->putJson('/tasks/'.$task->id, ['title' => 'Changed by another user', 'status' => 'completed', 'user_id' => $first->id])
            ->assertRedirect('/tasks');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Changed by another user', 'status' => 'completed']);
        $this->assertArrayNotHasKey('user_id', $task->fresh()->getAttributes());
        $this->deleteJson('/tasks/'.$task->id)->assertRedirect('/tasks');
        $this->assertDatabaseCount('tasks', 0);
        $this->assertDatabaseCount('users', 2);
    }

    private function useIdentity(bool $authenticated): void
    {
        if ($authenticated) {
            $user = User::factory()->create();
            $this->actingAs($user, 'web');
            $this->assertAuthenticatedAs($user, 'web');
        } else {
            $this->assertGuest('web');
        }
    }
}
