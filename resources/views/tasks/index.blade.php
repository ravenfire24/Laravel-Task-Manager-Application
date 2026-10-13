@extends('layouts.app')
@section('title', 'Tasks')
@section('content')
<div class="d-flex flex-wrap align-items-end justify-content-between mb-4 page-heading">
    <div>
        <h1>Tasks</h1>
        <p class="text-muted mb-0">Capture what needs doing. Make room for what comes next.</p>
    </div>
    <a href="{{ route('tasks.create') }}" class="btn btn-primary mt-3"><span aria-hidden="true">+</span> New task</a>
</div>
<div class="row mb-4" aria-label="Task totals">

    @foreach(['all' => 'Total tasks', 'pending' => 'Pending', 'completed' => 'Completed'] as $key => $label)

        <div class="col-4">
            <div class="summary-card {{ $key }}"><span class="summary-label">{{ $label }}</span><strong>{{ $counts[$key] }}</strong>
            </div>
        </div>

    @endforeach

</div>
<section class="card task-panel" aria-label="Task list">
    <div class="card-header bg-white p-4">
        <form action="{{ route('tasks.index') }}" method="get" class="row align-items-end">
            <div class="col-md-6 mb-3 mb-md-0">
                <label for="search">Search tasks</label>
                <input id="search" name="search" type="search" maxlength="255" class="form-control" placeholder="Search by title..." value="{{ $search }}">
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">All statuses</option>

                    @foreach(\App\Enums\TaskStatus::cases() as $option)
                        <option value="{{ $option->value }}" @selected($status === $option->value)>{{ $option->label() }}</option>
                    @endforeach

                </select>
            </div>
            <div class="col-md-3 d-flex">
                <button class="btn btn-outline-primary flex-grow-1" type="submit">Filter</button>

                @if($search !== '' || $status !== '')
                    <a href="{{ route('tasks.index') }}" class="btn btn-link ml-2">Clear</a>
                @endif

            </div>
        </form>
    </div>
    <div class="list-heading px-4 py-3 d-flex justify-content-between"><span>{{ $search !== '' || $status !== '' ? 'Filtered tasks' : 'All tasks' }}</span><span class="text-muted">{{ $tasks->total() }} {{ Str::plural('task', $tasks->total()) }}</span>
    </div>

    <div id="taskOrderList" data-order-url="{{ route('tasks.order') }}">
    @forelse($tasks as $task)

        <article class="task-row {{ $task->status->value }} px-4 py-4" data-id="{{ $task->id }}">
            <button type="button" class="drag-handle" hidden aria-label="Reorder {{ $task->title }}" title="Drag to reorder; use up/down arrow keys" aria-describedby="orderHelp"><span aria-hidden="true">&#8942;&#8942;</span></button>
            <form method="post" action="{{ route('tasks.status', $task) }}" class="task-toggle">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $task->status === \App\Enums\TaskStatus::Completed ? 'pending' : 'completed' }}">
                <button type="submit" class="completion-button {{ $task->status->value }}" aria-label="Mark {{ $task->title }} as {{ $task->status === \App\Enums\TaskStatus::Completed ? 'pending' : 'completed' }}">

                @if($task->status === \App\Enums\TaskStatus::Completed)
                    <span aria-hidden="true">&#10003;</span>
                @endif

                </button>
            </form>
            <div class="task-copy">
                <a class="task-title" href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a>

                @if($task->description)
                    <p class="task-description mt-1 mb-2">{{ Str::limit($task->description, 140) }}</p>
                @endif

                <div class="task-meta"><span class="badge badge-{{ $task->status === \App\Enums\TaskStatus::Completed ? 'success' : 'warning' }}">{{ $task->status->label() }}</span><span class="ml-2">Created <time datetime="{{ $task->created_at->toIso8601String() }}">{{ $task->created_at->format('M j, Y') }}</time></span>
                </div>
            </div>
            <div class="task-actions"><a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-outline-secondary" aria-label="Edit {{ $task->title }}">Edit</a>
                <button type="button" class="btn btn-sm btn-link text-danger" data-toggle="modal" data-target="#deleteTaskModal" data-delete-url="{{ route('tasks.destroy', $task) }}" data-task-title="{{ $task->title }}" aria-label="Delete {{ $task->title }}">Delete</button>
            </div>
        </article>

    @empty

        <div class="empty-state text-center p-5">
            <div class="empty-mark mb-3" aria-hidden="true">&#9745;</div>
            <h2>{{ $counts['all'] === 0 ? 'A fresh start' : 'No matching tasks' }}</h2>
            <p class="text-muted">{{ $counts['all'] === 0 ? 'Add your first task and take it one step at a time.' : 'Try another title or clear your filters.' }}</p><a class="btn btn-primary" href="{{ $counts['all'] === 0 ? route('tasks.create') : route('tasks.index') }}">{{ $counts['all'] === 0 ? 'Create your first task' : 'View all tasks' }}</a>
        </div>

    @endforelse
    </div>

    @if($tasks->hasPages())
        <div class="card-footer bg-white px-4 pt-4">{{ $tasks->links() }}</div>
    @endif

</section>
<p class="small text-muted mt-3">Use the circle beside a task to change its status.</p>
<p id="orderHelp" class="small text-muted" hidden>Drag the dotted handle to reorder tasks on this page, or focus it and use the up/down arrow keys. Changes save automatically.</p>
<p id="orderMessage" class="small" role="status" aria-live="polite"></p>
@include('tasks.partials.delete-modal')
@push('scripts')
    <script src="{{ asset('vendor/sortable/Sortable.min.js') }}"></script>
@endpush
@endsection

