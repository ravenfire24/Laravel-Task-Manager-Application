# Laravel Task Manager

A practical coding test submission built with **Laravel 13**, **Bootstrap 4.6.2**, Blade, and Eloquent. The application lets users create, view, edit, delete, and update the status of tasks.

**GitHub repository:** [Laravel-Task-Manager-Application](https://github.com/ravenfire24/Laravel-Task-Manager-Application)

**Live app:** [https://laravel-task-manager-application.vercel.app/tasks](https://laravel-task-manager-application.vercel.app/tasks)

The application runs locally. The optional live demo may require Vercel sign-in.

## Submission Instructions

1. **Local application:** the application has been verified running locally. Follow the [local setup](#local-setup) instructions to run it at http://localhost:8000.
2. **Project setup:** the [local setup](#local-setup) section lists the requirements, installation commands, and database setup.
3. **Final code on GitHub:** [Laravel-Task-Manager-Application](https://github.com/ravenfire24/Laravel-Task-Manager-Application).
4. **README contents:** this file includes an [overview of the approach](#approach), [assumptions](#assumptions), and [setup instructions](#local-setup).

## Approach

The application uses Laravel resource routes and a small controller for task CRUD. A shared form request validates both creation and editing. Eloquent models and migrations manage the database, while Blade views use Bootstrap 4 forms, cards, badges, alerts, pagination, and a deletion modal.

Task status is represented by a pending/completed enum. Ordering is handled by a separate controller that validates task IDs and saves their positions in a database transaction. Shared form and modal partials keep the views easy to maintain.

## Required features

- Create tasks with a required title and status, plus an optional description.
- View tasks with their title, creation date, and status.
- Edit task details with the same validation used for creation.
- Mark tasks as pending or completed, with distinct status badges.
- Delete tasks after confirming in a Bootstrap modal.
- Display validation errors, preserve entered values, and show success messages.

Blade escapes task content, all writes use CSRF protection, and missing tasks return a friendly 404 page. Error pages also cover expired sessions and unexpected server failures.

## Local setup

### Requirements

- PHP 8.3 or newer with Laravel's standard PHP extensions, including PDO SQLite and SQLite3.
- Composer 2.
- Git.

### Installation

```bash
git clone https://github.com/ravenfire24/Laravel-Task-Manager-Application.git
cd Laravel-Task-Manager-Application
composer run setup
php artisan serve
```

Open **http://localhost:8000**.

Run `composer run setup` once for a fresh checkout. It installs dependencies, copies `.env.example` to `.env`, generates the application key, creates the SQLite database, and runs migrations. No database server is needed locally.

To add optional sample tasks:

```bash
php artisan db:seed
```

After pulling updates that include new migrations, run `php artisan migrate`.

Bootstrap, jQuery, and SortableJS are included locally with their licenses. No Node build step or CDN connection is required.

## Bonus features

- **Search and filtering:** search by title, filter by pending/completed status, or combine both.
- **Pagination:** display 10 tasks per page while preserving search and status filters.
- **Drag-and-drop ordering:** reorder tasks with mouse or touch. Focus a dotted handle and use the up/down arrow keys for keyboard control. Changes save automatically and persist after refresh. Only the visible tasks on the current page move; other pages and filtered-out tasks keep their positions.

## Assumptions

- This is one shared task list without application-level user accounts.
- Pending is the initial form selection, but the server requires a valid status.
- Dates use UTC, new tasks appear at the top, and deletion is permanent.
- Local development uses SQLite; the live application uses Postgres.
- Credentials, local databases, and deployment connection files are excluded from Git.
