@extends('layouts.app')
@section('title', 'Task not found')
@section('content')
<div class="card text-center p-5">
    <p class="eyebrow">404</p>
    <h1>Task not found</h1>
    <p class="text-muted">This task may have been deleted. Head back to the list to find your next step.</p>
    <div><a class="btn btn-primary" href="{{ route('tasks.index') }}">Back to tasks</a>
    </div>
</div>
@endsection

