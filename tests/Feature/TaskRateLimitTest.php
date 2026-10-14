<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_creation_is_limited_without_saving_the_blocked_task(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->postJson('/tasks', ['title' => 'Allowed task', 'status' => 'pending'])->assertRedirect('/tasks');
        }

        $this->postJson('/tasks', ['title' => 'Blocked task', 'status' => 'pending'])
            ->assertTooManyRequests()->assertHeader('Retry-After');
        $this->assertDatabaseCount('tasks', 30);
        $this->assertDatabaseMissing('tasks', ['title' => 'Blocked task']);

        $this->travel(61)->seconds();
        $this->postJson('/tasks', ['title' => 'After cooldown', 'status' => 'pending'])->assertRedirect('/tasks');
    }

    public function test_all_mutating_routes_share_a_limit_and_other_clients_are_independent(): void
    {
        $tasks = Task::factory()->count(2)->create();
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->patchJson('/tasks/'.$tasks[0]->id.'/status', ['status' => 'pending'])->assertRedirect();
        }

        $this->putJson('/tasks/'.$tasks[0]->id, ['title' => 'Changed', 'status' => 'completed'])->assertTooManyRequests();
        $this->deleteJson('/tasks/'.$tasks[0]->id)->assertTooManyRequests();
        $this->patchJson('/tasks/order', ['ids' => $tasks->pluck('id')->all()])->assertTooManyRequests();
        $this->assertSame('pending', $tasks[0]->fresh()->status->value);
        $this->assertDatabaseCount('tasks', 2);

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->postJson('/tasks', ['title' => 'Other client', 'status' => 'pending'])->assertRedirect('/tasks');
    }

    public function test_read_requests_are_limited_separately_from_writes(): void
    {
        for ($attempt = 0; $attempt < 120; $attempt++) {
            $this->get('/tasks')->assertOk();
        }
        $this->get('/tasks/create')->assertTooManyRequests();
        $this->postJson('/tasks', ['title' => 'Write still allowed', 'status' => 'pending'])->assertRedirect('/tasks');
    }

    public function test_limiter_counters_are_shared_by_independent_limiter_instances(): void
    {
        $first = new RateLimiter(Cache::store(config('cache.limiter')));
        $second = new RateLimiter(Cache::store(config('cache.limiter')));
        $this->assertTrue($first->attempt('shared-security-counter', 1, fn () => true));
        $this->assertFalse($second->attempt('shared-security-counter', 1, fn () => true));
        $this->assertGreaterThan(0, DB::table('cache')->count());
    }
}
