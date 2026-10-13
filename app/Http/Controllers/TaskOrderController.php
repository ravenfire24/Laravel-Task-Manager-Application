<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskOrderController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'list', 'min:2', 'max:10'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:tasks,id'],
        ]);

        DB::transaction(function () use ($validated) {
            $tasks = Task::whereIn('id', $validated['ids'])
                ->orderBy('position')->orderByDesc('id')
                ->lockForUpdate()->get();

            abort_if($tasks->count() !== count($validated['ids']), 409, 'The list changed. Refresh and try again.');

            // Reuse this page's slots so filtered-out tasks and other pages stay put.
            $positions = $tasks->pluck('position');
            $tasksById = $tasks->keyBy('id');
            foreach ($validated['ids'] as $index => $id) {
                $task = $tasksById[$id];
                $task->position = $positions[$index];
                $task->save();
            }
        });

        return response()->json(['message' => 'Task order saved.']);
    }
}
