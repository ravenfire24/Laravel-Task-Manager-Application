@extends('layouts.app')
@section('title', $task->title)
@section('content')
<div class="form-page mx-auto">
    <a class="back-link" href="{{ route('tasks.index') }}">&larr; Back to tasks</a>
    <article class="card p-4 p-md-5 mt-4">
        <div><span class="badge badge-{{ $task->status === \App\Enums\TaskStatus::Completed ? 'success' : 'warning' }}">{{ $task->status->label() }}</span>
        </div>
        <h1 class="mt-3 task-detail-title">{{ $task->title }}</h1>
        <p class="small text-muted">Created {{ $task->created_at->format('M j, Y') }} &middot; Updated {{ $task->updated_at->format('M j, Y') }}</p>
        <div class="task-detail-description my-4">{{ $task->description ?? 'No description added.' }}</div>
        <div class="d-flex flex-wrap align-items-center border-top pt-4">
            <a class="btn btn-primary mr-2 mb-2" href="{{ route('tasks.edit', $task) }}">Edit task</a>
            <form method="post" action="{{ route('tasks.status', $task) }}" class="mr-2 mb-2">
                @csrf
                @method('PATCH')<input type="hidden" name="status" value="{{ $task->status === \App\Enums\TaskStatus::Completed ? 'pending' : 'completed' }}">
                <button class="btn btn-outline-secondary">Mark {{ $task->status === \App\Enums\TaskStatus::Completed ? 'pending' : 'completed' }}</button>
            </form>
            <button class="btn btn-link text-danger mb-2" type="button" data-toggle="modal" data-target="#deleteTaskModal" data-delete-url="{{ route('tasks.destroy', $task) }}" data-task-title="{{ $task->title }}">Delete</button>
        </div>
    </article>
</div>
@include('tasks.partials.delete-modal')
@endsection

