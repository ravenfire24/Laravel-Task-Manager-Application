<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Review the project brief', 'Confirm the required fields, status options, and submission instructions.', TaskStatus::Completed],
            ['Build the task creation form', 'Keep validation messages close to the fields they describe.', TaskStatus::Pending],
            ['Test the full task lifecycle', 'Create a task, edit its details, change its status, and delete it.', TaskStatus::Pending],
            ['Write the setup instructions', null, TaskStatus::Pending],
            ['Check the mobile layout', 'Make sure the task actions are easy to use on a small screen.', TaskStatus::Completed],
        ] as [$title, $description, $status]) {
            Task::firstOrCreate(['title' => $title], compact('description', 'status'));
        }
    }
}
