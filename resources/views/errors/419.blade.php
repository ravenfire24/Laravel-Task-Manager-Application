@extends('layouts.app')
@section('title', 'Your session expired')
@section('content')
<div class="card text-center p-5">
    <p class="eyebrow">419</p>
    <h1>Your session expired</h1>
    <p class="text-muted">Go back to the task list and try again. Your last change was not saved.</p>
    <div><a class="btn btn-primary" href="{{ route('tasks.index') }}">Back to tasks</a>
    </div>
</div>
@endsection

