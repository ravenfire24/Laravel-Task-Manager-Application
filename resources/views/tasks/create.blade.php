@extends('layouts.app')
@section('title', 'New task')
@section('content')
<div class="form-page mx-auto">
    <a class="back-link" href="{{ route('tasks.index') }}">&larr; Back to tasks</a>
    <h1 class="mt-4">New task</h1>
    <p class="text-muted mb-4">Give your next step a name. You can add the details later.</p>
    <div class="card p-4 p-md-5">
        <form method="post" action="{{ route('tasks.store') }}">

            @include('tasks.partials.form', ['submitLabel' => 'Create task'])
        </form>
    </div>
</div>
@endsection

