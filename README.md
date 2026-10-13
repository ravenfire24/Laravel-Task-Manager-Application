# Laravel Task Manager

A Laravel 13 application for creating, viewing, editing, completing, and deleting tasks. Built with Blade, Eloquent, and Bootstrap 4.6.2.

**Live app:** [https://laravel-task-manager-application.vercel.app/tasks](https://laravel-task-manager-application.vercel.app/tasks)

Vercel sign-in may be required to access the live app.

## Features

- Required task title and status; optional description.
- Shared validation for creation and editing, with field errors and preserved input.
- Pending/completed status controls and distinct badges.
- Task list with creation dates and a separate detail page.
- Delete confirmation modal and success messages.
- Search by title, filter by status, and pagination with 10 tasks per page.
- Drag-and-drop ordering with mouse, touch, or keyboard arrow keys. The order persists after refresh; only tasks on the current page move.

## Run locally

Requires **PHP 8.3+**, **Composer 2**, and PHP's standard Laravel extensions, including PDO SQLite and SQLite3.

```sh
git clone https://github.com/ravenfire24/Laravel-Task-Manager-Application.git
cd Laravel-Task-Manager-Application
composer run setup
php artisan serve
```

Open **http://localhost:8000**. Setup installs dependencies, creates `.env` and the application key, creates the SQLite database, and runs migrations. Run setup once for a fresh checkout.

Optional sample tasks: `php artisan db:seed`. After pulling new migrations, run `php artisan migrate`.

Bootstrap, jQuery, and SortableJS are bundled locally with their licenses. No Node build or CDN connection is required.

## Code structure

| File | Responsibility |
| --- | --- |
| `TaskController` | CRUD, search, pagination, and status updates |
| `TaskOrderController` | Validated order updates inside a database transaction |
| `TaskRequest` | Shared create/edit validation |
| `Task` / `TaskStatus` | Eloquent model and pending/completed enum |
| `database/migrations` | Task schema and saved ordering |
| `resources/views/tasks` | Blade pages, shared form, and delete modal |

Blade escapes task content. Writes use CSRF protection, and route model binding handles missing tasks. Friendly error pages cover missing records, expired sessions, and server errors.

## Verification

```sh
php artisan test
vendor/bin/pint --test
```

**15 feature tests, 105 assertions** cover CRUD, validation, both status transitions, filtering, pagination, escaped content, and saved ordering. Tests use an isolated in-memory SQLite database. GitHub Actions runs tests and formatting checks on PHP 8.3, 8.4, and 8.5.

Live HTTP checks also passed for CRUD, status changes, order persistence, validation, and frontend assets. Visual layout and actual drag gestures still need a manual browser check.

## Deployment

Vercel is connected to this repository. Pushes to **main** automatically deploy production; other branches create previews. Production and previews have separate Neon Postgres databases.

`vercel.json` configures the community PHP runtime and static assets. The deployment script runs migrations through the direct database connection; application requests use the pooled connection. Sessions use encrypted cookies, temporary files hold compiled views, and logs go to stderr.

Environment variables are managed in Vercel: `APP_KEY`, `APP_URL`, `DATABASE_URL`, `DATABASE_URL_UNPOOLED`, and the production PHP settings. Builds require `DB_CONNECTION=pgsql`, `DB_SSLMODE=require`, and `APP_ENV=production`. Keep the application key stable and migrations backward-compatible.

## Assumptions

This practical test uses one shared task list without application-level user accounts. Dates use UTC, new tasks start at the top, and deletion is permanent. Local development uses SQLite; deployment uses Postgres. Secrets, local databases, runtime tools, and Vercel connection files are excluded from Git.
