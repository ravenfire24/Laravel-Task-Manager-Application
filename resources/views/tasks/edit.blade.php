@extends('layouts.app')
@section('title', 'Edit task')
@section('content')
<div class="form-page mx-auto">
    <a class="back-link" href="{{ route('tasks.index') }}">&larr; Back to tasks</a>
    <h1 class="mt-4">Edit task</h1>
    <p class="text-muted mb-4">Update the details or change where things stand.</p>
    <div class="card p-4 p-md-5">
        <form method="post" action="{{ route('tasks.update', $task) }}">
            @method('PUT')
            @include('tasks.partials.form', ['submitLabel' => 'Save changes'])
        </form>
    </div>
</div>
@endsection

