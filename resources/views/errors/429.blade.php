@extends('layouts.app')
@section('title', 'Please slow down')
@section('content')
<div class="card text-center p-5">
    <h1>Please slow down</h1>
    <p class="text-muted">Too many requests. Wait a minute before trying again.</p>
    <div><a class="btn btn-primary" href="{{ route('tasks.index') }}">Back to tasks</a></div>
</div>
@endsection
