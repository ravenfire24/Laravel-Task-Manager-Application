# Deploying to Vercel

`vercel.json` uses the community [vercel-php runtime](https://github.com/vercel-community/php), pinned to `0.9.0`, and Vercel's static builder. Retain the repository configuration.

## GitHub setup

1. Complete the [local setup and tests](../README.md).
2. Import a GitHub repository you can push to, or use the existing connected Vercel project. Choose the repository root, framework **Other**, and Node **22.x**. Leave build/install command and output-directory overrides unset, matching the existing project.
3. Select production branch `main` and enable Git deployments. Configure variables and protection before serving the demo. Provision separate production/preview PostgreSQL databases; the current project uses Neon.
4. Supply each environment's pooled request URL, direct migration URL, and stable key as described below. Deploy/redeploy the intended commit after configuring them.

The [Vercel GitHub integration](https://vercel.com/docs/git/vercel-for-github) automatically creates production deployments from `main` and previews from other branches. `.github/workflows/tests.yml` runs CI, not deployment. Its `CI passed` check can be required in GitHub and Vercel settings; see [CI instructions](CI.md). The workflow does not configure a release gate itself, so do not assume automatic production deployment waits for tests.

## Environment variables

Set these for both production and preview. Use sensitive/Secret values for keys and URLs. Environment changes take effect on a new deployment.

| Variable | Hosted value or purpose |
|---|---|
| `APP_KEY` | Stable Laravel key, different for production and preview. |
| `APP_ENV`, `APP_DEBUG` | `production`, `false`, for both hosted environments. |
| `APP_URL` | That environment's HTTPS application URL. |
| `DB_CONNECTION` | `pgsql`. |
| `DATABASE_URL` | Pooled request URL. `DB_URL`, if set, overrides it; remove stale/blank overrides. |
| `DATABASE_URL_UNPOOLED` | Direct migration URL; alternatively use `DB_MIGRATION_URL`. The first nonempty value wins. |
| `DB_SSLMODE` | `verify-full`, enforced by the Vercel connector after URL parsing. |
| `DB_SSLROOTCERT` | Usually unset: uses `certs/cacert.pem`. An override must point to a trusted public CA file available to the function. |
| `SESSION_DRIVER`, `SESSION_ENCRYPT` | `database`, `true`. |
| `SESSION_SECURE_COOKIE` | `true`; HTTPS is required. |
| `SESSION_LIFETIME` | Optional; default 120 minutes. |
| `CACHE_STORE` | `array` for ephemeral general caching. |
| `CACHE_LIMITER` | `database` (also the code default) for persistent throttling. |
| `LOG_CHANNEL`, `QUEUE_CONNECTION` | `stderr`, `sync`. |

`VERCEL=1` is supplied by Vercel and enables proxy trust, TLS enforcement, and the Neon workaround. Do not set it for ordinary local development. For PostgreSQL outside Vercel, `DB_VERIFY_SERVER_CERTIFICATE=true` enables connector verification; `pdo_pgsql` is required.

Generate a hosted key in a disposable initialized checkout with `php artisan key:generate`, then securely transfer its ignored `.env` `APP_KEY` to the target's Vercel secret. Generate production and preview keys separately. Do not replace existing keys or run the fresh setup script in a hosted installation.

Dashboard overrides must be reviewed alongside `vercel.json`. The build script checks key/URL presence, but does not enforce every secure session/debug setting.

## Build and runtime

The PHP runtime executes the Composer script named `vercel`. Here it runs package discovery and `scripts/vercel-build.php`. That script connects through the direct URL, runs `migrate --force`, verifies the task table, and fails the build on errors. It does not seed data.

All migrations are needed: task/position tables, sessions, and cache/locks. Coordinate schema changes across concurrent deployments: each build runs migrations without a deployment-wide lock.

`api/index.php` redirects compiled views and package manifests to writable temporary storage before loading Laravel. Tasks, sessions, and limiter counters use PostgreSQL; the function filesystem is not durable storage.

Static assets come from `public/`. Keep the anchored `/vendor/` rule in `.vercelignore`: it excludes Composer dependencies without excluding `public/vendor/`. Local databases, environment files, tests, and tools are excluded from uploads. Do not upload a production `.env`.

## Verification and recovery

After READY, open `/tasks` through the intended protection. Check assets, creation/editing/status changes, ordering persistence, validation, and deletion with temporary tasks you remove afterward. Check runtime errors in Vercel's logs. Build success alone does not validate request connectivity or all dashboard settings.

Keep access protection enabled for every exposed URL, including custom domains. It does not create application accounts or isolate admitted visitors' tasks.

Before schema changes, have a recoverable backup and verify compatibility with served code. Deployment rollback restores code, not database migrations. Do not use `migrate:fresh`, seeders, or automatic migration rollback as a production recovery shortcut. Restore/promote a known compatible deployment and handle database recovery separately.

## Troubleshooting

| Symptom | Check |
|---|---|
| Vercel protection page | Authenticate with an allowed account; do not disable protection to fix an app request. |
| Missing `APP_KEY` | Set the target's stable key and redeploy. |
| Missing PostgreSQL/migration URLs | Check target scoping, pooled/direct URLs, and `DB_URL` precedence. |
| Certificate/connection failure | Check host, credentials, CA availability, direct/pooled URLs, and provider connectivity. Keep verification enabled. |
| Neon endpoint routing failure | Confirm `VERCEL=1` and a Neon host. The provider derives its endpoint and preserves nonempty `PGOPTIONS`. |
| Missing session/cache table or runtime 500 | Verify all migrations and that pooled/direct URLs target the same environment's database. Inspect sanitized type/SQLSTATE/query fingerprint. |
| Repeated 419/lost input | Check stable key, encrypted database sessions, secure cookies, HTTPS, and matching cookies/token. |
| Assets return 404 | Preserve `vercel.json` static routes and `public/vendor/` upload. |
| Push creates no expected deployment | Check GitHub access/connection, Git deployments, production branch, and ignored-build settings. |
| CI passes but deployment fails | CI tests use SQLite and its build check uses disposable PostgreSQL. It does not check hosted credentials or enable release gates automatically. Inspect build/runtime logs. |

References: [`vercel.json`](../vercel.json), [`composer.json`](../composer.json), [`scripts/vercel-build.php`](../scripts/vercel-build.php), [`api/index.php`](../api/index.php), and [CI isolation and checks](CI.md).
