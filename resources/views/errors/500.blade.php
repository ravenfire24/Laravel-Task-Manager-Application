@extends('layouts.app')
@section('title', 'Something went wrong')
@section('content')
<div class="card text-center p-5">
    <p class="eyebrow">500</p>
    <h1>Something went wrong</h1>
    <p class="text-muted">We could not finish that request. Please try again in a moment.</p>
    <div><a class="btn btn-primary" href="{{ route('tasks.index') }}">Back to tasks</a>
    </div>
</div>
@endsection

