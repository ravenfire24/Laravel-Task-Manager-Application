# Laravel Task Manager

A small task manager built with Laravel 13, Blade, Eloquent, and Bootstrap 4.6.2. Create tasks, read their details, edit them, change their status, and delete them after confirming in a modal.

## Requirements

- PHP 8.3 or newer with Laravel's standard extensions, plus PDO SQLite and SQLite3.
- Composer 2.
- Git.

Bootstrap, jQuery, and SortableJS are included in `public/vendor` with their licenses. There is no Node build step or CDN dependency.

## Local setup

```sh
git clone https://github.com/ravenfire24/Laravel-Task-Manager-Application.git
cd Laravel-Task-Manager-Application
composer run setup
php artisan serve
```

Open **http://localhost:8000**. The setup script installs dependencies, copies `.env.example` if needed, generates an application key, creates the SQLite file, and runs migrations. Run it once for a new checkout; generating a new key on an existing installation invalidates its sessions.

After pulling updates that add migrations, run `php artisan migrate` before starting the app.

To add optional example tasks:

```sh
php artisan db:seed
```

The seeder can be rerun without duplicating its sample titles. For MySQL, change the database settings in `.env` and run `php artisan migrate` against an empty database. The included SQLite defaults require no database server.

### This workspace on Windows

A portable PHP runtime and Composer were downloaded into ignored `.tools` for local verification. If they are still present, you can run without changing your PATH:

```powershell
.tools/php/php.exe artisan serve
.tools/php/php.exe artisan test
```

Other checkouts should install PHP and Composer normally and use the setup commands above.

## Approach

- `TaskController` contains the small resource actions, list filters, and explicit status update.
- `TaskRequest` shares create/edit validation: a title up to 255 characters, optional description up to 10,000 characters, and a required pending/completed status.
- `TaskStatus` is a backed enum used for validation, model casting, and form options.
- A migration defines the task table; Eloquent handles all database writes. Status and position are indexed. Lists use saved positions; new tasks start at the top.
- Blade escapes task content. All writes use CSRF protection and appropriate HTTP methods. Route model binding returns 404 for missing tasks.
- Views share the layout, task form, and delete modal. JavaScript connects the selected task to Bootstrap's modal and saves drag-and-drop order; CRUD and status updates use normal forms.
- Validation keeps entered values and shows field errors. Successful changes show flash messages. Friendly error views cover missing tasks, expired sessions, server failures, and maintenance. Laravel logs unexpected exceptions; set `APP_DEBUG=false` outside local development.

## Bonus features

Search by title, filter by status, combine both filters, and paginate ten tasks per page with filters preserved. Summary counts show totals across all tasks. There is also a separate detail page and optional sample data. Drag-and-drop reordering is supported with mouse and touch via bundled SortableJS. Focus a dotted handle and use the up/down arrow keys for keyboard reordering. Orders save to the database automatically. Reordering swaps only the current page's visible task positions, so other pages and filtered-out tasks remain in place. Failed saves restore the displayed order and show a message.

## Assumptions

This is a single-user practical test with one shared task list and no authentication. Pending is selected initially, but the server still requires a valid status. Dates use the application's UTC timezone. Tasks are permanently deleted only after the UI confirmation. Do not expose this shared app publicly without adding authentication and authorization.

## Verification

```sh
php artisan test
vendor/bin/pint --test
```

Feature tests use an isolated in-memory SQLite database. They exercise CRUD, both status transitions, validation, old input, optional descriptions, filtering, pagination, escaped content, missing records, modal markup, saved ordering, filtered subsets, page boundaries, invalid order payloads, and new-task placement. GitHub Actions runs the tests and formatting check on PHP 8.3, 8.4, and 8.5.

Local verification passed: **15 feature tests**, Laravel Pint, Composer manifest validation, Blade compilation, and a real HTTP task lifecycle including CSRF rejection. Browser automation was unavailable in the build session, so visual layout and modal interaction still need the manual check below.

For a manual browser check: create a task, submit an empty title, edit both its fields and status, toggle its completion circle, search/filter it, then test both **Keep task** and **Delete task** in the modal. Drag a task using its dotted handle, reload to verify persistence, and repeat with a status filter and on page two. Focus a handle and press the up/down arrow keys. Check the same flow on a narrow screen.

## Submission

Repository: [ravenfire24/Laravel-Task-Manager-Application](https://github.com/ravenfire24/Laravel-Task-Manager-Application).

No deployment is required. The application is designed for a local PHP server; the supplied Vercel link is not used for hosting.
