# Continuous integration

GitHub Actions runs `.github/workflows/tests.yml` on pushes, pull requests, manual dispatch, and every Monday at 06:23 UTC. The scheduled run checks the default branch for newly published dependency advisories.

## Checks

| Job | What it verifies |
|---|---|
| Quality and static analysis | Composer manifest/lock consistency, Pint formatting, PHP syntax, application JavaScript syntax, PHPStan with Larastan at level 5, and workflow syntax with actionlint. |
| Tests (PHP 8.3/8.4/8.5) | Separate Unit and Feature suites. Feature tests exercise real HTTP/database workflows with in-memory SQLite. Integration tests still run after a unit-test failure, but that failure remains fatal. |
| Dependency and secret checks | Composer advisories and Gitleaks scanning all fetched Git history, with matched values fully redacted. |
| Production dependency and build check | Production-only Composer installation, Blade compilation, the existing Vercel Composer build script against disposable PostgreSQL 17, and required static assets/CA file. |
| CI passed | Succeeds only when every required job succeeds. Failures, cancellations, and skipped required jobs cannot produce a passing gate. |

Actions are pinned to commit hashes. Downloaded actionlint and Gitleaks binaries are pinned to versions and checked against committed SHA-256 hashes before execution. Dependabot checks Composer dependencies and GitHub Actions weekly; binary versions/checksums need reviewed manual updates. The PostgreSQL service uses the `postgres:17` image tag.

Composer's download cache is keyed by OS, PHP version, and lockfile. CI does not cache application configuration or environment files. JUnit XML is retained for seven days; environment files, application logs, build bundles, and database contents are not uploaded.

## Repository secrets and database isolation

**No repository secrets or custom repository variables are required.** CI does not reference GitHub repository secrets or retrieve Vercel environment files. Checkout has read-only permissions and does not persist Git credentials.

Each application job generates its own temporary key in an ignored `.env` file. Keys are not printed or uploaded. PHPUnit forces both process and server environment settings to the testing environment, SQLite `:memory:`, and a null database URL. The Laravel test base rejects persistent databases before migration traits run.

The build job uses only a temporary PostgreSQL service on `127.0.0.1:5432`, database `task_manager_ci`, user `task_ci`, and the public test-only password defined in the workflow. Both request and migration URLs point to that service. Its data disappears with the runner. CI runs ordinary migrations on this empty database; it never connects to a hosted production or preview database.

TLS is disabled only for the runner's local database service. Hosted Vercel certificate verification and deployment settings remain unchanged. Never substitute real credentials or hosted URLs into the CI build job.

If Gitleaks fails, investigate the redacted finding and rotate any confirmed credential. Do not disable scanning or add broad exclusions to obtain a passing build. Vendored Bootstrap, jQuery, and SortableJS are not covered by Composer's advisory database; review their advisories and versions separately.

## Local checks

After the normal development setup:

```sh
composer validate --strict
php vendor/bin/pint --test
php vendor/bin/phpstan analyse --no-progress --memory-limit=512M
node --check public/js/tasks.js
php artisan config:clear
php vendor/bin/phpunit --testsuite=Unit
php vendor/bin/phpunit --testsuite=Feature
composer audit --locked
```

Node is needed for the JavaScript syntax check, not for application asset compilation. Workflow validation and history scanning require the pinned actionlint/Gitleaks tools shown in the workflow. Do not run `composer run vercel` locally with a hosted database merely to reproduce CI: that command runs migrations.

## Deployment and required checks

Vercel continues to deploy through its existing Git integration. This workflow does not deploy, promote, change deployment protection, or require `VERCEL_TOKEN`, `VERCEL_ORG_ID`, or `VERCEL_PROJECT_ID`. Keep hosted keys and database credentials in the appropriate Vercel environment as described in [deployment instructions](DEPLOYMENT.md).

After this workflow has run, an administrator can require **CI passed** in GitHub branch protection/rulesets and select it as a GitHub-provider [Vercel Deployment Check](https://vercel.com/docs/deployment-checks). Those dashboard settings are not enabled by this YAML. Until configured, production deployments do not wait for this pipeline. If an existing rule requires the old workflow's `test` check, replace it with `CI passed` after verifying the new workflow.

A Vercel promotion gate does not prevent build-time migrations: the existing hosted build script still runs them before promotion. Use backward-compatible schema changes and coordinate deployments. Rollback restores code, not database changes.

## Limits

The build job validates PostgreSQL migrations, not concurrent reorder behavior or a hosted TLS handshake. The PHPUnit database suite remains SQLite-specific. There is no browser/E2E suite or coverage threshold. The local build check is not a full Vercel runtime bundle/deployment test.
