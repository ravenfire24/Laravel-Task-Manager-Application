@extends('layouts.app')
@section('title', 'Back shortly')
@section('content')
<div class="card text-center p-5">
    <p class="eyebrow">503</p>
    <h1>Back shortly</h1>
    <p class="text-muted">Task Manager is temporarily unavailable. Please try again soon.</p>
    <div><a class="btn btn-primary" href="{{ route('tasks.index') }}">Back to tasks</a>
    </div>
</div>
@endsection

