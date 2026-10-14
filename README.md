# Laravel Task Manager

**Repository:** [GitHub](https://github.com/ravenfire24/Laravel-Task-Manager-Application)

## Approach

Laravel 13 and Eloquent handle task creation, viewing, editing, deletion, and status updates. Migrations manage the database, shared validation checks forms, and Bootstrap 4 styles the Blade views, including the deletion confirmation modal.

## Assumptions

- One shared task list without login or per-user ownership.
- Tasks are Pending or Completed; descriptions are optional.
- Local development uses SQLite, and deletion is permanent.

## Setup and local verification

Requires PHP 8.3+ with Laravel/SQLite extensions, Composer 2, and Git on PATH.

```bash
git clone https://github.com/ravenfire24/Laravel-Task-Manager-Application.git
cd Laravel-Task-Manager-Application
composer run setup
composer run test
php artisan serve
```

Open http://localhost:8000. Setup installs dependencies, creates `.env`, generates a key, and runs SQLite migrations. Run setup once for a fresh checkout; it replaces the application key if repeated.

## Bonus features

- Search by title and filter by status.
- Pagination with 10 tasks per page.
- Persistent drag-and-drop ordering, with keyboard controls.
