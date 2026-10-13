<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Tasks') &middot; Task Manager</title>
        <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/tasks.css') }}">
    </head>
    <body>
        <header class="app-header">
            <nav class="container d-flex align-items-center justify-content-between py-3" aria-label="Main navigation">
                <a class="brand" href="{{ route('tasks.index') }}"><span class="brand-mark" aria-hidden="true">&#10003;</span> Task Manager</a>
            </nav>
        </header>
        <main class="container py-5" id="main-content">

            @if(session('success'))

                <div class="alert alert-success alert-dismissible fade show" role="status">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Dismiss message"><span aria-hidden="true">&times;</span>
                    </button>
                </div>

            @endif

            @if($errors->any())

                <div class="alert alert-danger" role="alert">
                    <strong>Please check your entries.</strong>
                    <ul class="mb-0 mt-2">

                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>
                </div>

            @endif

            @yield('content')
        </main>
        <footer class="container pb-4 text-muted small">Task Manager <span class="mx-2" aria-hidden="true">/</span> Keep the next step in sight.</footer>
        <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
        <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('js/tasks.js') }}"></script>
    </body>
</html>

