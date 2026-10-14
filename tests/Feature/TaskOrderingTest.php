<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_order_uses_ascending_positions_and_descending_ids_for_ties(): void
    {
        $tasks = Task::factory()->count(4)->create();
        foreach ([-10, 0, 0, 20] as $index => $position) {
            $tasks[$index]->position = $position;
            $tasks[$index]->saveQuietly();
        }
        $expected = [$tasks[0]->id, $tasks[2]->id, $tasks[1]->id, $tasks[3]->id];

        $this->get('/tasks')->assertOk()->assertViewHas('tasks', fn ($page) => $page->pluck('id')->all() === $expected);
    }

    public function test_reordering_reuses_nonconsecutive_slots_and_preserves_unselected_tasks(): void
    {
        $tasks = Task::factory()->count(4)->create();
        foreach ([-10, 0, 20, 100] as $index => $position) {
            $tasks[$index]->position = $position;
            $tasks[$index]->saveQuietly();
        }

        $this->patchJson('/tasks/order', ['ids' => [$tasks[3]->id, $tasks[0]->id]])->assertOk();

        $this->assertSame(-10, $tasks[3]->fresh()->position);
        $this->assertSame(100, $tasks[0]->fresh()->position);
        $this->assertSame(0, $tasks[1]->fresh()->position);
        $this->assertSame(20, $tasks[2]->fresh()->position);
        $expected = [$tasks[3]->id, $tasks[1]->id, $tasks[2]->id, $tasks[0]->id];
        $this->get('/tasks')->assertOk()->assertViewHas('tasks', fn ($page) => $page->pluck('id')->all() === $expected);
    }
}
