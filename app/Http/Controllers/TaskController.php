<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\TaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
        ]);
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? '';

        $tasks = Task::query()
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->inDisplayOrder()
            ->paginate(10)
            ->withQueryString();

        $counts = [
            'all' => Task::count(),
            'pending' => Task::where('status', TaskStatus::Pending)->count(),
            'completed' => Task::where('status', TaskStatus::Completed)->count(),
        ];

        return view('tasks.index', compact('tasks', 'search', 'status', 'counts'));
    }

    public function create(): View
    {
        return view('tasks.create', ['task' => new Task(['status' => TaskStatus::Pending])]);
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        DB::transaction(fn () => Task::create($request->validated()));

        return to_route('tasks.index')->with('success', 'Task created.');
    }

    public function show(Task $task): View
    {
        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task): View
    {
        return view('tasks.edit', compact('task'));
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return to_route('tasks.index')->with('success', 'Task updated.');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $task->update($request->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ]));

        return back()->with('success', 'Task status updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return to_route('tasks.index')->with('success', 'Task deleted.');
    }
}
