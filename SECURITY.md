# Security notes

This application is a shared task-list demo. It has no application login or
per-user ownership. Vercel deployment protection restricts access to the hosted
demo; anyone granted access can edit all tasks. Do not store private information
or disable that protection without adding authentication and task authorization.

## Safeguards

- Laravel validation, escaped Blade output and CSRF protection apply to task writes.
- Each client IP can make 120 task reads and 30 task writes per minute. The write
  quota covers creation, editing, deletion, status changes and ordering together.
  Counters use the database so they persist between serverless requests.
- Vercel sessions use encrypted database storage and secure cookies. Long invalid
  forms retain their input without putting the description into a browser cookie.
- Database failures remain visible through exception type, SQLSTATE and query
  fingerprint. Task values and database server error details are omitted from logs.
- PostgreSQL on Vercel verifies certificate trust and hostname, even if a provider
  connection URL specifies a weaker TLS mode. Public trust roots are in `certs/`.
- CI pins third-party actions to commit hashes and checks Composer advisories.

## Deployment settings

Vercel dashboard values override repository defaults. Production and preview need
`SESSION_DRIVER=database`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`,
`DB_SSLMODE=verify-full`, `CACHE_LIMITER=database`, and `APP_DEBUG=false`.
Keep their databases and application keys separate. Existing session and cache
migrations must be applied before serving requests. The database session switch
starts fresh sessions; task data is unaffected.

## Verification

Run `php artisan test`, `vendor/bin/pint --test`, and `composer audit --locked`.
Security regressions cover rate limits and expiry, shared counters, safe database
logging, long-form sessions and encryption, TLS downgrade prevention, and CI pins.

## Remaining considerations

Bootstrap 4 is required by the coding test and is past upstream maintenance.
Review the bundled Bootstrap, jQuery and SortableJS versions for advisories when
updating them; Composer does not audit those browser libraries. Refresh the public
CA bundle periodically. Proxy trust relies on Vercel controlling forwarded headers.
Historical Git author metadata is public and is not removed by ignoring `.env`.
No history rewrite or credential rotation is performed by these fixes.
