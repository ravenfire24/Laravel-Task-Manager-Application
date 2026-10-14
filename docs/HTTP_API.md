# HTTP endpoints

These routes are registered in `routes/web.php` and use Laravel's web middleware. There is no `/api/tasks` prefix, bearer-token authentication, login endpoint, or private per-user task API. `api/index.php` is the Vercel entry point, not a route prefix.

Guests and signed-in application users share the same access. Hosted deployment protection must be satisfied separately before reaching Laravel.

## Request and response contract

- Reads return HTML, including when `Accept: application/json` is sent.
- CRUD/status writes redirect on success; they do not return JSON task resources. Ordering returns JSON.
- Send `Accept: application/json` for JSON validation/error responses. Otherwise, validation redirects back with flashed errors/old input, and errors render HTML.
- Writes must satisfy request-forgery verification. Non-browser clients should GET a form, keep its session cookies, and send the meta `csrf-token` as `X-CSRF-TOKEN` or `_token`. Forms already do this; valid same-origin Fetch Metadata is also accepted by Laravel.
- Bodies may be JSON (`Content-Type: application/json`) or form data. HTML forms use POST plus `_method=PUT`, `PATCH`, or `DELETE` for those verbs.

## Routes

| Method | Path | Input | Success |
|---|---|---|---|
| GET | `/` | — | 302 to `/tasks` |
| GET | `/tasks` | Optional `search`, `status`, `page` query parameters | 200 HTML list |
| GET | `/tasks/create` | — | 200 HTML form |
| POST | `/tasks` | Task fields | 302 to `/tasks` |
| GET | `/tasks/{task}` | Existing ID | 200 HTML detail |
| GET | `/tasks/{task}/edit` | Existing ID | 200 HTML form |
| PUT or PATCH | `/tasks/{task}` | Task fields; title/status still required | 302 to `/tasks` |
| PATCH | `/tasks/{task}/status` | Required `status` | 302 back to the previous URL |
| DELETE | `/tasks/{task}` | Existing ID | 302 to `/tasks` |
| PATCH | `/tasks/order` | Required `ids` list | 200 JSON |

GET routes also accept HEAD. `/tasks/order` is a static PATCH endpoint, not a task ID. Inspect routes with `php artisan route:list --except-vendor`.

## Validation and business rules

| Field | Rule |
|---|---|
| `title` | Required string, maximum 255 characters. |
| `description` | Optional/nullable string, maximum 10,000 characters. |
| `status` | Required lowercase `pending` or `completed`; display labels use capitals. |
| `search` | Optional/nullable string, maximum 255 characters. |
| List `status` filter | Optional/nullable `pending` or `completed`. |
| `ids` | List of 2–10 distinct integer IDs, each referring to an existing task. |

Laravel trims ordinary strings and converts empty strings to `null`. On edit, omitting description preserves it; `null` or an empty string clears it. Extra fields are excluded from task validation. ID, position, timestamps, and ownership fields cannot be assigned through task writes.

Search uses parameterized SQL `LIKE` on title. `%` and `_` are SQL wildcards; case sensitivity follows database behavior. Results sort by ascending position, then descending ID, with ten per page. Summary counts cover the whole shared list, not the filtered page.

Creation sets position to the negative ID. Ordering assigns selected tasks their existing slots in submitted ID order; unselected tasks keep their positions. The UI submits the visible page, but the server accepts any valid selection of 2–10 existing IDs and does not enforce page membership.

Example task payload:

```json
{"title":"Review proposal","description":"Check scope","status":"pending"}
```

PUT and PATCH of the main task endpoint both require title/status. For status alone, use `/tasks/{task}/status`.

Example ordering from a loaded application page, replacing the IDs with existing tasks:

```js
const response = await fetch('/tasks/order', {
    method: 'PATCH',
    credentials: 'same-origin',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ ids: [2, 1] })
});
const result = await response.json();
```

Successful response:

```json
{"message":"Task order saved."}
```

Fetch follows CRUD redirects by default, so its eventual successful response for those routes is an HTML page.

## Errors and limits

| Status | Meaning |
|---|---|
| 404 | A bound task ID is missing. Unknown IDs in an ordering body instead produce validation errors. |
| 409 | A selected task disappears after validation, before locking. JSON message: `The list changed. Refresh and try again.` |
| 419 | Failed request-forgery verification, such as a missing/wrong session token without accepted origin metadata. |
| 422 | Invalid input with JSON negotiation; body has `message` and field-keyed `errors`. Form validation redirects instead. |
| 429 | Quota exceeded; inspect `Retry-After`. |
| 500 | Unexpected failure. With debug off, JSON is `{"message":"Server Error"}`; HTML uses the error page. |

Reads share 120 requests/minute per IP; task writes share 30/minute per IP. There is no task-ownership 401/403 policy. Integration tests in `tests/Feature/TaskWorkflowTest.php`, `TaskCsrfBoundaryTest.php`, and `TaskDatabaseFailureTest.php` exercise these workflows through Laravel and SQLite.
