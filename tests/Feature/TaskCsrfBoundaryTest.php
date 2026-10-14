<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\EnforcesCsrf;
use Tests\TestCase;

class TaskCsrfBoundaryTest extends TestCase
{
    use EnforcesCsrf, RefreshDatabase;

    #[DataProvider('identitiesAndTokens')]
    public function test_forged_writes_are_rejected_before_business_logic_for_guests_and_signed_in_users(bool $authenticated, ?string $suppliedToken): void
    {
        config(['app.debug' => false]);
        $validToken = $this->enableCsrfProtection();
        $this->assertFalse($this->app->runningUnitTests());
        if ($authenticated) {
            $user = User::factory()->create();
            $this->actingAs($user, 'web');
            $this->assertAuthenticatedAs($user, 'web');
        } else {
            $this->assertGuest('web');
        }
        $tasks = Task::factory()->count(2)->create();
        $original = Task::orderBy('id')->get()->map(fn (Task $task) => $task->getAttributes())->all();
        if ($suppliedToken === null) {
            $this->withoutHeader('X-CSRF-TOKEN');
        } else {
            $this->withHeader('X-CSRF-TOKEN', $suppliedToken);
        }

        $requests = [
            ['POST', '/tasks', ['title' => 'Forged creation', 'status' => 'pending']],
            ['PUT', '/tasks/'.$tasks[0]->id, ['title' => 'Forged update', 'status' => 'completed']],
            ['PATCH', '/tasks/'.$tasks[0]->id.'/status', ['status' => 'completed']],
            ['DELETE', '/tasks/'.$tasks[0]->id, []],
            ['PATCH', '/tasks/order', ['ids' => $tasks->pluck('id')->all()]],
        ];
        foreach ($requests as [$method, $uri, $payload]) {
            $this->json($method, $uri, $payload)->assertStatus(419)->assertJsonMissingPath('trace');
            $this->assertSame($original, Task::orderBy('id')->get()->map(fn (Task $task) => $task->getAttributes())->all());
        }

        // The same session succeeds with its valid token; access was rejected for forgery.
        $this->withHeader('X-CSRF-TOKEN', $validToken)
            ->postJson('/tasks', ['title' => 'Legitimate creation', 'status' => 'pending'])->assertRedirect('/tasks');
        $this->assertDatabaseHas('tasks', ['title' => 'Legitimate creation']);
        $this->assertDatabaseCount('tasks', 3);
    }

    public static function identitiesAndTokens(): array
    {
        return [
            'guest without token' => [false, null],
            'guest with wrong token' => [false, 'wrong-session-token'],
            'signed in without token' => [true, null],
            'signed in with wrong token' => [true, 'wrong-session-token'],
        ];
    }
}
