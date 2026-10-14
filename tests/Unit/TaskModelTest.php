<?php

namespace Tests\Unit;

use App\Enums\TaskStatus;
use App\Models\Task;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ValueError;

class TaskModelTest extends TestCase
{
    #[DataProvider('statuses')]
    public function test_stored_statuses_cast_to_the_expected_case_and_display_label(string $value, TaskStatus $status, string $label): void
    {
        $task = new Task(['status' => $value]);

        $this->assertSame($status, $task->status);
        $this->assertSame($label, $task->status->label());
        $this->assertSame($value, $task->getAttributes()['status']);
    }

    public static function statuses(): array
    {
        return [
            'pending' => ['pending', TaskStatus::Pending, 'Pending'],
            'completed' => ['completed', TaskStatus::Completed, 'Completed'],
        ];
    }

    public function test_unknown_status_is_rejected_instead_of_being_silently_cast(): void
    {
        $task = new Task;
        $this->expectException(ValueError::class);

        $task->status = 'archived';
    }

    public function test_mass_assignment_updates_details_without_replacing_identity_order_or_timestamps(): void
    {
        $task = new Task;
        $managed = ['id' => 7, 'position' => -7, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'];
        $task->setRawAttributes($managed, true);
        $task->fill([
            'title' => 'Changed title', 'description' => 'Changed description', 'status' => 'completed',
            'id' => 999, 'position' => -999, 'created_at' => '2000-01-01', 'updated_at' => '2000-01-01',
        ]);

        $this->assertSame('Changed title', $task->title);
        $this->assertSame('Changed description', $task->description);
        $this->assertSame(TaskStatus::Completed, $task->status);
        foreach ($managed as $field => $value) {
            $this->assertSame($value, $task->getAttributes()[$field]);
        }
    }
}
