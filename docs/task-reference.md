# 📋 Task Reference

Complete reference for all available deployment tasks in Klytron Deployer. This guide covers every task, its purpose, parameters, and usage examples.

## 🎯 Core Deployment Tasks

### `deploy`

Main deployment task that orchestrates the entire deployment process.

```bash
# Deploy to all hosts
vendor/bin/dep deploy

# Deploy to specific host
vendor/bin/dep deploy myapp.com

# Deploy with options
vendor/bin/dep deploy --tag=v1.0.0 --revision=abc123
```

**Available Options:**
- `--tag`: Deploy specific tag
- `--revision`: Deploy specific revision
- `--branch`: Deploy specific branch
- `--dry-run`: Simulate deployment without making changes
- `--fast`: Skip some checks for faster deployment
- `-v, --verbose`: Verbose output
- `-q, --quiet`: Quiet output

**Task Flow:**
1. `deploy:prepare`
2. `deploy:lock`
3. `deploy:release`
4. `deploy:update_code`
5. `deploy:shared`
6. `deploy:writable`
7. `deploy:vendors`
8. `deploy:clear_paths`
9. `deploy:symlink`
10. `deploy:unlock`
11. `deploy:cleanup`

### `deploy:prepare`

Prepare deployment environment.

```bash
vendor/bin/dep deploy:prepare
```

**What it does:**
- Validate configuration
- Check SSH connectivity
- Verify server requirements
- Create necessary directories

### `deploy:lock`

Lock deployment to prevent concurrent deployments.

```bash
vendor/bin/dep deploy:lock
```

**What it does:**
- Create deployment lock file
- Prevent multiple simultaneous deployments
- Set deployment timestamp

### `deploy:release`

Create new release directory.

```bash
vendor/bin/dep deploy:release
```

**What it does:**
- Create new release directory
- Set release timestamp
- Prepare release environment

### `deploy:update_code`

Update application code from repository.

```bash
vendor/bin/dep deploy:update_code
```

**What it does:**
- Clone/pull code from repository
- Checkout specified branch/tag
- Update submodules if configured

### `deploy:shared`

Create shared files and directories.

```bash
vendor/bin/dep deploy:shared
```

**What it does:**
- Create shared directories
- Copy shared files
- Set proper permissions

### `deploy:writable`

Set writable permissions on directories.

```bash
vendor/bin/dep deploy:writable
```

**What it does:**
- Set writable permissions on configured directories
- Use configured writable mode (chmod/chown/acl)
- Apply permissions recursively

### `deploy:vendors`

Install Composer dependencies.

```bash
vendor/bin/dep deploy:vendors
```

**What it does:**
- Run `composer install`
- Install production dependencies
- Optimize autoloader

### `deploy:clear_paths`

Clear specified paths before deployment.

```bash
vendor/bin/dep deploy:clear_paths
```

**What it does:**
- Clear configured paths
- Remove temporary files
- Clean up cache directories

### `deploy:symlink`

Create symlink to current release.

```bash
vendor/bin/dep deploy:symlink
```

**What it does:**
- Create `current` symlink to new release
- Update web server configuration
- Switch traffic to new release

### `klytron:deploy:create:server_symlink`

Create or update the primary web server symlink from the configured `application_public_html` to the deployed `public_dir_path`.

```bash
vendor/bin/dep klytron:deploy:create:server_symlink
```

**What it does:**
- Ensures parent directory exists
- Removes existing symlink/file; backs up existing directory to timestamped path
- Creates symlink: `application_public_html` → `public_dir_path`

### `klytron:deploy:create:server_symlink_aliases`

Create additional alias symlinks for multiple domains, using `application_public_html_aliases`.

```bash
vendor/bin/dep klytron:deploy:create:server_symlink_aliases
```

**Configuration:**
```php
// Simple paths (ownership falls back to host http_user/http_group or parent owner)
set('application_public_html_aliases', [
  '/var/www/example1.com/public_html',
  '/var/www/example2.com/public_html',
]);

// Per-alias ownership
set('application_public_html_aliases', [
  ['path' => '/var/www/example1.com/public_html', 'user' => 'www-data', 'group' => 'www-data'],
  ['path' => '/var/www/example2.com/public_html'], // uses host http_user/http_group
]);

// Single string also supported
// set('application_public_html_aliases', '/var/www/example.com/public_html');
```

**What it does:**
- For each alias path, ensures parent exists
- Removes existing symlink/file; backs up existing directory
- Creates symlink: `alias_public_html` → `public_dir_path`
- Sets symlink ownership priority: per‑alias `user:group` → host `http_user:http_group` → parent owner (Virtualmin suexec friendly)

Tip: You can set host defaults via `klytron_configure_host('host', ['http_user' => 'klytron', 'http_group' => 'klytron'])`.

---

### `klytron:laravel:deploy:db:import`

Laravel database import task with smart file selection.

```bash
vendor/bin/dep klytron:laravel:deploy:db:import
```

**Selection logic:**
- Scans `database/live-db-exports` (configurable via `db_import_path`)
- If multiple files are found:
  - Prefer the file with the most recent datetime embedded in the filename (supports `YYYYMMDD_HHMMSS` and `YYYYMMDDHHMMSS`)
  - If filenames do not contain datetimes, fallback to newest by file modification time
- If only one file is found, it is used
- Encrypted files (`*.sql.encrypted`) are automatically decrypted via `klytron:file:decrypt` (with `file:decrypt` fallback)

Set the import directory with:
```php
set('db_import_path', 'database/live-db-exports');
```

When `database_type` is `'sqlite'`, this task automatically delegates to `klytron:laravel:deploy:db:import:sqlite`.

### `klytron:laravel:deploy:db:import:sqlite`

Laravel SQLite database replacement task with maintenance protection.

```bash
vendor/bin/dep klytron:laravel:deploy:db:import:sqlite
```

**What it does:**
1. Scans `db_import_path` (default: `database/live-db-exports`) for `.sqlite`, `.db`, `.sqlite.encrypted`, or `.db.encrypted` files.
2. Automatically decrypts encrypted files using `klytron:file:decrypt` if found.
3. Selects the newest file (supporting datetime-sorted filenames or modification timestamps).
4. Puts application into maintenance mode (`artisan down`) to prevent write race conditions during file replacement.
5. Replaces target SQLite database file at `sqlite_database_path` (default: `{{deploy_path}}/shared/database/database.sqlite`).
6. Ensures ownership (`http_user:http_group`) and permissions (`664`).
7. Clears Laravel caches (`artisan cache:clear`).
8. Brings application back online (`artisan up`).

Configuration:
```php
set('database_type', 'sqlite');
set('db_import_path', 'database/live-db-exports');
set('sqlite_database_path', '{{deploy_path}}/shared/database/database.sqlite');
```

### `deploy:unlock`

Unlock deployment after completion.

```bash
vendor/bin/dep deploy:unlock
```

**What it does:**
- Remove deployment lock file
- Allow future deployments
- Clean up lock state

### `deploy:cleanup`

Clean up old releases.

```bash
vendor/bin/dep deploy:cleanup
```

**What it does:**
- Remove old releases beyond keep limit
- Clean up temporary files
- Free up disk space

## 🎯 Framework-Specific Tasks

### Laravel Tasks

#### `deploy:laravel`

Main Laravel deployment task.

```bash
vendor/bin/dep deploy:laravel
```

**What it does:**
- Run Laravel-specific deployment steps
- Execute Artisan commands
- Configure Laravel environment

#### `deploy:laravel:env`

Configure Laravel environment file.

```bash
vendor/bin/dep deploy:laravel:env
```

**What it does:**
- Copy environment file to release
- Set proper permissions
- Validate environment configuration

#### `deploy:laravel:storage`

Configure Laravel storage.

```bash
vendor/bin/dep deploy:laravel:storage
```

**What it does:**
- Create storage directories
- Set storage permissions
- Configure storage symlinks

#### `deploy:laravel:cache`

Clear and rebuild Laravel caches.

```bash
vendor/bin/dep deploy:laravel:cache
```

**What it does:**
- Clear application cache
- Clear config cache
- Clear route cache
- Clear view cache

#### `deploy:laravel:migrate`

Run Laravel database migrations.

```bash
vendor/bin/dep deploy:laravel:migrate
```

**What it does:**
- Run database migrations
- Handle migration errors
- Log migration results

#### `deploy:laravel:seed`

Run Laravel database seeders.

```bash
vendor/bin/dep deploy:laravel:seed
```

**What it does:**
- Run database seeders
- Seed production data
- Handle seeding errors

#### `deploy:laravel:passport`

Configure Laravel Passport.

```bash
vendor/bin/dep deploy:laravel:passport
```

**What it does:**
- Install Passport keys
- Configure OAuth settings
- Set up API authentication

#### `deploy:laravel:optimize`

Optimize Laravel application.

```bash
vendor/bin/dep deploy:laravel:optimize
```

**What it does:**
- Optimize autoloader
- Cache configuration
- Optimize routes
- Optimize views

### Yii2 Tasks

#### `deploy:yii2`

Main Yii2 deployment task.

```bash
vendor/bin/dep deploy:yii2
```

**What it does:**
- Run Yii2-specific deployment steps
- Configure Yii2 applications
- Set up Yii2 environment

#### `deploy:yii2:init`

Initialize Yii2 application.

```bash
vendor/bin/dep deploy:yii2:init
```

**What it does:**
- Run Yii2 initialization
- Set up application structure
- Configure Yii2 settings

#### `deploy:yii2:migrate`

Run Yii2 database migrations.

```bash
vendor/bin/dep deploy:yii2:migrate
```

**What it does:**
- Run Yii2 migrations
- Handle migration errors
- Log migration results

## 🎯 Database Tasks

This package has no generic `deploy:database:*` tasks. Use the real ones:

- `klytron:deploy:backup:create` — pre-deploy snapshot (`{{deploy_path}}/current` copy + `MYSQL_PWD`/`PGPASSWORD` DB dump; gated by `shouldBackupBeforeDeployment` / `shouldBackupDatabase`)
- `klytron:laravel:deploy:database:complete` — invokes `klytron:laravel:deploy:db:migrate` (when `shouldRunMigration`) and `klytron:laravel:deploy:db:import` (when `shouldImportDbFile`); skips everything when `database_type` is `none`
- `klytron:laravel:deploy:db:import` (+ `:sqlite` variant) — smart file selection from `db_import_path`, encrypted-file auto-decrypt
- `klytron:laravel:deploy:passport:install` — `passport:install --force`, keys, personal-access client (gated by `supports_passport` + `shouldRunPassportInstall`)

Full details in the Laravel section below.

## 🎯 Asset Tasks

### `klytron:node:build`

Generic Node.js build dispatcher. Auto-detects Vite vs Mix.

```bash
vendor/bin/dep klytron:node:build
```

**What it does:**
- Detects `vite.config.*` plus `npm run build` → runs `klytron:node:vite:build`
- Detects `npm run production` (Mix) and Laravel project → runs `klytron:laravel:node:mix:build`

### `klytron:node:vite:build`

Generic Vite build with environment variable support and Node.js compatibility.

```bash
vendor/bin/dep klytron:node:vite:build
```

**What it does:**
- Detects Node.js automatically: tries `node`, then `nodejs`, then NVM (using `.nvmrc` when present, otherwise LTS)
- Installs dependencies (`npm ci` or `npm install`, with Puppeteer Chromium download skipped and retry via mirror)
- Loads selected `.env` variables into the build environment securely (no `.env` contents printed to logs)
- Runs `npm run build` (falls back to `NODE_OPTIONS="--openssl-legacy-provider"` for older OpenSSL)

**Configuration options:**
- `supports_vite` (bool, default: true): Enable/disable Vite build step
- `vite_build_command` (string, default: `npm run build`): Override build command
- `vite_env_vars` (array, default: `['APP_NAME','APP_ENV','APP_URL','VITE_PUSHER_APP_KEY','VITE_PUSHER_APP_CLUSTER']`): Env vars to expose to the build
- `npm_cache_dir` (string, default: `{{deploy_path}}/.npm-cache`): NPM cache location on server
- `npm_registry` (string, default: `https://registry.npmjs.org`): Primary NPM registry
- `npm_registry_mirror` (string, default: `https://registry.npmmirror.com`): Mirror registry used on failure

**Node/NVM behavior:**
- If `node` is not in PATH, the task tries `nodejs`
- If neither exists, the task attempts to activate NVM from `$HOME/.nvm/nvm.sh`
- When `.nvmrc` exists in the release, it is respected via `nvm install && nvm use`
- Without `.nvmrc`, the latest LTS is selected (`nvm use --lts`)

**Recommendations:**
- Install NVM on servers and add `.nvmrc` (e.g., `24` or `v24.5.0`) to projects for deterministic Node versions
- Rotate any secrets if a previous deploy printed `.env` to logs (older task versions could emit `.env`)
- Keep `supports_vite` enabled only when using Vite; disable if assets are pre-built or not needed

### `klytron:laravel:node:mix:build`

Laravel-specific Mix build with environment variable support.

```bash
vendor/bin/dep klytron:laravel:node:mix:build
```

**What it does:**
- Installs dependencies (`npm ci` or `npm install` with Puppeteer downloads skipped)
- Exposes `MIX_*`, `NODE_*`, `APP_URL`, `ASSET_URL` to build
- Runs `npm run production` with OpenSSL legacy provider fallback

## 🎯 Testing Tasks

This package has no `test` / `test:ssh` / `test:database` / `test:env` tasks.
Use the real validation entry points (all local, no SSH needed):

```bash
# Full CI smoke test: config + env files + placeholders + task graph
vendor/bin/dep klytron:plan

# The five validate subtasks as one group
vendor/bin/dep klytron:validate:basic

# Individually
vendor/bin/dep klytron:validate:deploy_path_parent
vendor/bin/dep klytron:validate:domain
vendor/bin/dep klytron:validate:env_files
vendor/bin/dep klytron:validate:placeholders
vendor/bin/dep klytron:validate:remote_user
```

See "Validate subtasks" below for what each checks.

## 🎯 Utility Tasks

This package defines `klytron:deploy:info` / `klytron:laravel:deploy:info`
(read-only deployment summaries) and `klytron:delete:project` (double-confirmed
destructive cleanup scoped to `deploy_path`). `rollback` and `deploy:unlock`
/ `deploy:cleanup` are native Deployer tasks. There are no `current` /
`releases` / `status` / `rollback:list` tasks in this package.

```bash
# Read-only deployment summary
vendor/bin/dep klytron:deploy:info

# Release a stuck lock, clean old releases, roll back
vendor/bin/dep deploy:unlock
vendor/bin/dep deploy:cleanup
vendor/bin/dep rollback
```

## 🎯 Custom Tasks

### Creating Custom Tasks

Use standard Deployer `task()` syntax:

```php
// Add custom task
task('deploy:custom', function () {
    run('echo "Running custom task"');
    run('{{bin/php}} {{release_or_current_path}}/artisan custom:command');
})->desc('Run custom deployment task');
```

### Task Dependencies

Tasks can depend on other tasks or deployer primitives:

```php
task('deploy:custom', function () {
    // Task implementation
});
// (Dependencies are usually handled via hooks or explicit lists rather than a dependencies array in Deployer 7)
```

### Task Hooks

Hook your custom tasks into the deployment flow:

```php
// Add hook
after('deploy:symlink', 'deploy:custom');
before('deploy:vendors', 'deploy:custom_check');
```

## 🎯 Task Examples

### Complete Laravel Deployment

```bash
# Full Laravel deployment
vendor/bin/dep deploy

# Laravel deployment with specific options
vendor/bin/dep deploy --tag=v1.0.0 --verbose
```

### Database Operations

```bash
# Backup database (pre-deploy snapshot)
vendor/bin/dep klytron:deploy:backup:create

# Run Laravel migrations (+ optional DB import, per init answers)
vendor/bin/dep klytron:laravel:deploy:database:complete
vendor/bin/dep klytron:laravel:deploy:db:migrate
```

### Asset Building

```bash
# Generic dispatcher (Vite preferred, Mix only for Laravel projects)
vendor/bin/dep klytron:node:build

# Laravel Vite build (node_modules hardlink cache + package-lock.json check)
vendor/bin/dep klytron:laravel:node:vite:build

# Laravel Mix build
vendor/bin/dep klytron:laravel:node:mix:build
```

### Testing and Validation

```bash
# CI smoke test: validates config + task graph, no SSH needed
vendor/bin/dep klytron:plan

# Local validation subtasks (deploy path, domain, env files, placeholders, user)
vendor/bin/dep klytron:validate:basic

# Release a stuck deploy lock, then roll back
vendor/bin/dep deploy:unlock
vendor/bin/dep rollback
```

### Utility Operations

```bash
# Show deployment info
vendor/bin/dep klytron:deploy:info

# Release a stuck lock, clean old releases
vendor/bin/dep deploy:unlock
vendor/bin/dep deploy:cleanup

# Rollback to previous release
vendor/bin/dep rollback
```

## 🎯 Klytron Utility Tasks

### `klytron:deploy:start_timer`

Starts the deployment timer and displays the start timestamp.

**Usage:**
```php
task('deploy', [
    'klytron:deploy:start_timer',
    // ... other tasks
])->desc('Deploy application');
```

**Output:**
```
⏱️ Deployment started at 2026-03-31 13:23:05
```

**What it does:**
- Records the deployment start time
- Displays the start timestamp
- Initializes deployment metrics tracking

### `klytron:deploy:end_timer`

Ends the deployment timer and displays completion information.

**Usage:**
```php
task('deploy', [
    // ... other tasks
    'klytron:deploy:end_timer',
])->desc('Deploy application');
```

**Output:**
```
⏱️ Deployment completed at 2026-09-30 15:30:00
⏱️ Deployment completed in 42s
```

**What it does:**
- Calculates deployment duration
- Displays the completion timestamp
- Shows the total elapsed time

### `klytron:deploy:check_pushed`

Pre-flight safety task that verifies all local Git commits have been pushed to the upstream remote before Deployer connects.

**Usage:**
```php
task('deploy', [
    'klytron:deploy:start_timer',
    'deploy:unlock',
    'klytron:deploy:check_pushed',
    // ...
]);
```

**What it does:**
- Runs `git log @{u}.. --oneline` locally.
- Throws an immediate `RuntimeException` if unpushed commits exist, preventing deployments of stale code.
- Automatically bypassed if `check_git_pushed` is configured to `false` or if running in detached HEAD.

### `klytron:cache:vendor`

High-speed Composer optimization task. Reuses the `vendor/` directory from the previous release via hardlinks (`cp -al`).

**Usage:**
- Runs automatically before `deploy:vendors` when `previous_release` exists.
- Reduces `composer install` from minutes down to ~2 seconds because unchanged packages are already hardlinked in place.

### `klytron:laravel:node:vite:build`

Builds frontend assets with Vite using `node_modules` caching and `package-lock.json` change detection.

**What it does:**
- Copies `node_modules/` from previous release via hardlinks (`cp -al`).
- Compares `package-lock.json` against the previous release.
- If dependencies are identical, skips `npm install` and runs `npm run build` directly (cuts 3–6 minutes off builds).
- Injects environment variables (`APP_URL`, `APP_ENV`, etc.) into Vite build process.

### `klytron:laravel:filament:assets`

Publishes Filament v5 panel vendor assets into `public/`.

**Usage:**
- Runs automatically before `deploy:symlink` when `supports_filament` is enabled or `filament/filament` is detected in `composer.json`.
- Executes `php artisan filament:assets` to ensure all stylesheets and scripts are published and cache-busted for the new release.

### Consumer-derived generic tasks

Migrated from real project `deploy.php` files so consumers configure instead
of forking. All read plain project config — see `configuration-reference.md`.

| Task | Config keys | What it replaces |
|---|---|---|
| `klytron:set:domain-from-env` | `domain_env_var` (default `APP_URL_DOMAIN`), `domain_env_file` (default `.env.production`) | Per-project "read domain from env file" tasks |
| `klytron:deploy:replace-tokens` | `domain_replace_files` (list, `{{…}}` placeholders allowed), `domain_replace_search` (default `example.com`) | Per-project sed loops over ad-loader/config files |
| `klytron:check:binaries` | `required_binaries` (list) | Per-project optimizer/tool presence warnings; warning-only, never fails — parses `command -v` stdout (never `test()`, which false-negatives on some hosts) so a mirror hiccup can't fail a good deploy |
| `klytron:laravel:decrypt:paths` | `decrypt_paths` (release-relative dirs) | Per-project `artisan file:decrypt <dir>` loops |
| `klytron:laravel:extra-commands` | `extra_artisan_commands` (verbatim command strings) | Per-project sitemap/link-fixup tasks — wire once in the flow |
| `klytron:laravel:check:web-php` | none (reads local `composer.json` + live URL) | Per-project "is the domain PHP new enough" warnings — asks the running site over HTTP (any non-success status is the signal) and never parses server internals, so it stays advisory and can't misread FPM pools |

Wire the flow-point tasks in the project's `deploy` list (e.g.
`extra-commands` after finalize, `check:web-php` at the end); the rest run
standalone or wherever the flow needs them.

Why `klytron:opcache:reset` reads `application_public_domain`/`application_public_url` only:
it never reads Deployer's provision `domain` key because that key is an `ask()`
closure — touching it in a non-interactive deploy blocks forever on a "Domain:"
prompt. The reset curls the live site SAPI (Virtualmin `fcgi-bin/phpX.Y.fcgi` /
`php-cgi` workers are untouched by a systemd php-fpm reload) with a short-lived
token-gated script, then deletes it. Disable with `set('skip_opcache_reset', true)`.

### `klytron:deploy:health_check`

Automated HTTP verification task that ensures the application is live and returning HTTP 200 post-symlink. Warns by default; set `health_check_fail_on_error => true` to fail the deploy on a non-expected code (use when a green-but-broken deploy is worse than a failed one).

**Usage:**
```php
task('deploy', [
    // ...
    'deploy:symlink',
    'deploy:unlock',
    'deploy:cleanup',
    'klytron:deploy:health_check',
    'klytron:deploy:end_timer',
]);
```

**What it does:**
- Performs HTTP request using `curl` against the deployed `application_public_url` or domain.
- Confirms HTTP 200 (or configured expected status) and logs response status and latency.

### `klytron:plan`

CI smoke test and dry-run task. Validates local configuration, environment files, and path placeholders without requiring SSH keys or remote network access.

**Usage:**
```bash
vendor/bin/dep klytron:plan
```

**Output:**
```
📋 ===== KLYTRON DEPLOYMENT PLAN & VALIDATION =====
Application:  my-app
Repository:   git@github.com:org/my-app.git
Deploy Path:  /var/www/my-app
Domain:       myapp.com
Database:     mysql
Vite:         enabled
Filament:     enabled
✅ Local environment file verified: .env.production
✅ All path placeholders resolved
✅ Deploying as non-root user: deploy
✅ All plan configurations validated successfully!
```

### `klytron:validate:basic`

Composite validation task that executes `klytron:validate:deploy_path_parent`, `klytron:validate:domain`, `klytron:validate:env_files`, `klytron:validate:placeholders`, and `klytron:validate:remote_user`.

### `klytron:fpm:reload`

Reloads the PHP-FPM service (e.g. `systemctl reload php8.3-fpm`) and then runs `klytron:opcache:reset`.

### `klytron:opcache:reset`

Clears OPcache through the **live site SAPI** (required on Virtualmin hosts that use `fcgi-bin/phpX.Y.fcgi` / `php-cgi`, where systemd php-fpm reload does not touch the running CGI workers). Writes a short-lived token-gated script under `public/`, curls the domain (and localhost with `Host`), then deletes the script. Set `skip_opcache_reset` to `true` to disable.

### `klytron:deploy:clean_repo`

Explicit task to completely wipe Deployer's remote `.dep/repo` cache when a corrupt git object occurs on the server.

Use `klytron:deploy:fix_repo` for routine deploys (it only clears stale
`index.lock` files and tops up `safe.directory` — it never deletes
`.dep/repo`, so the fast git cache survives). Reach for
`klytron:deploy:clean_repo` only when the cache itself is corrupt and a full
re-clone is required.

### `klytron:deploy:fix_repo`

Routine git-cache first aid, safe to run on every deploy (before
`deploy:update_code`).

```bash
vendor/bin/dep klytron:deploy:fix_repo
```

**What it does (verified in `klytron-tasks.php`):**
- Removes stale lock files only: `<deploy_path>/.git/index.lock` and `<deploy_path>/.dep/repo/index.lock`
- Runs `git config --global --add safe.directory` for the deploy path, the repo cache, and `deploy_path_parent` (fixes "dubious ownership" without touching data)
- Never deletes `.dep/repo` — the cached clone is preserved for fast updates

### `klytron:deploy:fix_git_ownership`

Ownership/`safe.directory` repair after `deploy:update_code` (also auto-hooked
via `after('deploy:update_code', 'klytron:deploy:fix_git_ownership')`).

```bash
vendor/bin/dep klytron:deploy:fix_git_ownership
```

**What it does (verified in `klytron-tasks.php`):**
- Adds the `.dep/repo` cache and the current `release_path` to `safe.directory`
- `chown -R http_user:http_group` + `chmod -R 755` on the repo cache so later git operations don't hit "dubious ownership"

### `klytron:deploy:access_permissions`

Final ownership/permission sweep over the active release. Single remote script
(one SSH round-trip), setgid directory mode, symlink-inode aware.

```bash
vendor/bin/dep klytron:deploy:access_permissions
```

**What it does (verified in `klytron-tasks.php`):**
- One remote `set -eu` script: `chown http_user:http_group` over the release, then `chmod` dirs (`default_dir_permissions | 02000`, e.g. `2755` setgid) and files (`default_file_permissions`)
- Prunes `node_modules`, `.git`, and `.npm-cache` from the recursive `find` passes (the historic deploy-stall source)
- `chown -h` symlink-inode sweep over the release plus a `find -H … -maxdepth 1 -type l` pass on the served dir — plain `chown` follows links (fixes targets, leaves root-owned inodes, which 403s under Apache `SymLinksIfOwnerMatch`), so inodes are re-pointed explicitly, never followed, never recursive
- Laravel extras when `project_type === 'laravel'`: `chmod -R laravel_storage_permissions` on `storage`, `laravel_cache_permissions` on `bootstrap/cache`
- `.htaccess`, shared `storage`/`bootstrap/cache`, and the `public_html` inode handled inside the same script

### `klytron:upload:env:production`

Uploads the local env file to `shared/` so every release shares it.

```bash
vendor/bin/dep klytron:upload:env:production
```

**What it does (verified in `klytron-tasks.php`):**
- Uploads `env_file_local` → `<shared_dir_path>/<env_file_remote>` (e.g. `.env.production` → `shared/.env`)
- Skips cleanly when `env_file_local` is empty/`false` (cron runners, static tooling with no env file) — same opt-out as `klytron:validate:env_files`
- Never prints secrets: with `debug_env_upload=true` the preview goes through `klytron_mask_secrets()`; remote validation uses quiet `[ -f ] && grep -q` existence checks so values never cross SSH stdout

### Validate subtasks

`klytron:validate:basic` runs all five; each also runs standalone:

| Task | Purpose | Code location |
|---|---|---|
| `klytron:validate:deploy_path_parent` | Aborts unless `deploy_path_parent` is an absolute path (blocks deploys to the wrong location) | `deployment-kit-core.php` → `klytron_validate_deploy_path_parent()` |
| `klytron:validate:domain` | Warns when `application_public_domain`/`application_public_html` are missing; notes whether the public-html dir exists yet | `klytron-tasks.php` |
| `klytron:validate:env_files` | Fails when the local env file is missing — unless `env_file_local` is `false`/empty, then it skips cleanly (no-env projects) | `klytron-tasks.php` |
| `klytron:validate:placeholders` | Fails on unresolved `${…}` placeholders in `deploy_path_parent`, `application_public_html`, `public_dir_path` (e.g. `klytron_set_domain()` never called) | `klytron-tasks.php` |
| `klytron:validate:remote_user` | Warns on `remote_user === 'root'`; confirms non-root otherwise. Default deployer is `deployer` (honors `DEPLOY_USER`); root-run hosts need the sudoers setup in `docs/quick-start.md#non-root-deployment-and-sudoers-setup` | `klytron-tasks.php`, `deployment-kit-core.php` (`klytron_configure_host`) |

### Confirm / backup / success / info / delete tasks

| Task | Behavior |
|---|---|
| `klytron:deploy:confirm` | Intentionally empty (confirmation is handled by interactive questions); kept for backward-compatible flows |
| `klytron:laravel:deploy:confirm` | Group: `klytron:laravel:init:questions` (unattended-CI keys documented in `configuration-reference.md`) |
| `klytron:deploy:backup:create` | Copies `{{deploy_path}}/current` to a timestamped `backups/` dir; `mysqldump` via `MYSQL_PWD` (never on the command line) when credentials exist; gated by `shouldBackupBeforeDeployment` |
| `klytron:laravel:deploy:backup:create`, `klytron:laravel:backup:pre_deploy` / `post_deploy`, `klytron:laravel:backup:manage`, `klytron:laravel:backup:health_check` | Laravel backup variants wired into the Laravel flows |
| `klytron:deploy:success` | Timing summary + live URL from `application_public_domain` |
| `klytron:laravel:deploy:success` | Invokes the generic success task, then app/path/symlink details; `after()`-hooked to `klytron:system:restart` (PHP-FPM reload, idempotent per deploy) |
| `klytron:deploy:info`, `klytron:laravel:deploy:info` | Read-only deployment summary (app, repo, branch, host, paths, domain, timeouts) |
| `klytron:delete:project` | Destructive: double-confirmed (`askConfirmation` + type `DELETE`), scoped strictly to `deploy_path`, then `rm -rf {{deploy_path}}` |

### Assets / fonts / sitemap / images tasks

Gating flags are read per run — nothing is installed or generated unless its
flag is on:

| Task | Gate (default) | Behavior |
|---|---|---|
| `klytron:assets:map` | `supports_vite`/`supports_mix` + `cleanup_assets` (true) | Maps Vite hashed assets for DB URL compatibility (`AssetMappingTask::mapAssets()`) |
| `klytron:assets:cleanup` | `cleanup_assets` (true) | Removes problematic `.htaccess` files from build dirs |
| `klytron:fonts:verify` | `verify_fonts` (false) | Verifies webfont delivery; skips when disabled |
| `klytron:fonts:debug` | none (always runs diagnostics) | Dumps font-loading diagnostics |
| `klytron:sitemap:generate` / `verify` / `check` | `supports_sitemap` (false) | Generate → verify written → HTTP accessibility check |
| `klytron:images:optimize` | `optimize_images` (false) | Post-deploy image compression (`ImageOptimizationTask::optimizeImages()`) |
| `klytron:laravel:deploy:generate:sitemap` | `supports_sitemap` (false) | Laravel variant: tries `sitemap:generate`, then `app:sitemap-generate`, then `sitemap:create`; warns and continues when none exist |
| `klytron:laravel:fix:assets:permissions` | checks `assets_directory` (default `public/web-assets`) | `chmod 644/755` sweep over fonts, CSS, JS, images when that dir exists |

### Hidden metrics / env-decrypt stubs

Nine placeholder tasks are `->hidden()` by design (verified in
`klytron-tasks.php`) so `dep list` stays clean — they log a skip line and do
nothing until real implementations land: `klytron:metrics:display`,
`klytron:metrics:export`, `klytron:metrics:compare`, `klytron:metrics:start`,
`klytron:metrics:end`, `klytron:env:decrypt`,
`klytron:env:decrypt:production`, `klytron:env:validate`,
`klytron:env:setup`. (The working Laravel decryption task is the separate
`klytron:laravel:env:decrypt`, gated on `LARAVEL_ENV_ENCRYPTION_KEY`.)

### Laravel tasks (`recipes/klytron-laravel-recipe.php`)

#### `klytron:laravel:deploy:db:migrate`

```bash
vendor/bin/dep klytron:laravel:deploy:db:migrate
```

- Gated by `shouldRunMigration` (set from the init questions / `auto_database_operation`); skips quietly otherwise
- SQLite branch: ensures the DB file via `artisan klytron:sqlite:setup --force`, then `artisan migrate --force`. On failure it prints the still-`Pending` rows from `migrate:status` so the exact schema gap is in the log, then fail-louds: the "continue anyway?" prompt defaults to **no**
- MySQL/Postgres branch: `artisan migrate --force`, fail-loud prompt on error

#### `klytron:laravel:deploy:passport:install`

Gated by `supports_passport` + `shouldRunPassportInstall`. Verifies
`vendor/laravel/passport/composer.json` exists, runs
`artisan passport:install --force`, generates keys
(`passport:keys --force`) when missing, and creates the personal-access
client. Fail-loud prompt on error.

#### `klytron:laravel:storage:link`

Gated by `supports_storage_link` (default true). Prefers the enhanced
`artisan klytron:storage:link-clean`, falls back to standard
`artisan storage:link`.

#### `klytron:laravel:deploy:cache:clear:all`

Gated by `shouldClearAllCaches` (default true). Runs `cache:clear`,
`config:clear`, `route:clear`, `view:clear`, and `clear-compiled` against
`{{release_or_current_path}}`.

#### `klytron:deploy:laravel:access_permissions`

Laravel companion to the generic sweep (auto-hooked with
`after('klytron:deploy:access_permissions', …)`). Single remote script with
`timeout 90` guards: `chmod`/`chown` on `storage` (`laravel_storage_permissions`)
and `bootstrap/cache` (`laravel_cache_permissions`), resolving symlinked paths
via `readlink -f` and skipping broken symlinks; `.env` set to `640`.

### Composite (group) tasks

| Task | Members |
|---|---|
| `klytron:deploy:environment:complete` | `klytron:upload:env:production` (upload runs exactly once per deploy) |
| `klytron:laravel:deploy:environment:complete` | `klytron:deploy:environment:complete` |
| `klytron:laravel:deploy:database:complete` | Invokes `db:migrate` when `shouldRunMigration`, `db:import` when `shouldImportDbFile`; skips all when `database_type` is `none` |
| `klytron:laravel:deploy:cache:complete` | `cache:clear:all` (when `shouldClearAllCaches`) + `artisan:config:cache` + `artisan:optimize` |
| `klytron:laravel:deploy:finalize:complete` | `klytron:deploy:finalize:complete` + `klytron:laravel:storage:link` |
| `klytron:deploy:finalize:complete` | `create:server_symlink` + `create:server_symlink_aliases` + `access_permissions` |
| `klytron:deploy:prepare:complete` / `klytron:laravel:deploy:prepare:complete` | `confirm` + `backup:create` when `shouldBackupBeforeDeployment` |
| `klytron:deploy:notify:complete` / `klytron:laravel:deploy:notify:complete` | `success` tasks above |
| `klytron:php:deploy:complete` / `klytron:php:deploy:minimal` | Full and minimal plain-PHP flows (`recipes/klytron-php-recipe.php`; minimal skips env/vendors/finalize) |

### Node build tasks (canonical names)

- `klytron:node:build` — generic dispatcher: Vite config + `build` script → `klytron:node:vite:build`; `production` (Mix) script on a Laravel project → `klytron:laravel:node:mix:build`. Anything else is a hard error.
- `klytron:laravel:node:vite:build` — Laravel Vite build used by the Laravel flows: hardlink-copies `node_modules` from the previous release, diffs `package-lock.json`, and skips `npm install` when dependencies are unchanged; restores execute bits on `node_modules/.bin` first (exit-126 guard).
- `klytron:laravel:node:mix:build` — Laravel Mix build (`npm run production`, `MIX_*`/`NODE_*`/`APP_URL`/`ASSET_URL` exposed, OpenSSL legacy-provider fallback).
- `klytron:node:vite:build` — framework-agnostic Vite build with the same NVM/`node`→`nodejs` detection, registry-mirror retry, and masked `.env` injection used outside Laravel flows.

### Server recipe (`recipes/klytron-server-recipe.php`)

- `klytron:server:deploy:configs` — copies (or symlinks) configured server files into the release. Configure with `server_config_files`, each item `{source, target, mode, overwrite}` (`source`/`target` relative to the release; optional `symlink`, `owner`, `group` override the `http_user:http_group` defaults):

```php
set('server_config_files', [
    ['source' => 'server/.htaccess.production', 'target' => 'public/.htaccess', 'mode' => 0644, 'overwrite' => true],
]);
after('deploy:shared', 'klytron:server:deploy:configs');
```

- `klytron:deploy:server:htaccess` — legacy single-`.htaccess` deploy (`htaccess_source`, default `server/.htaccess.production`); prefer `deploy:configs`.
- `klytron:laravel:deploy:htaccess` — deprecated alias, delegates to `klytron:server:deploy:configs`.


## 🎯 Task Configuration

### Task Timeouts

Configure task timeouts:

```php
set('default_timeout', 1800); // 30 minutes
set('deploy_timeout', 3600);  // 1 hour
```

### Task Parallelization

Run tasks in parallel:

```php
set('parallel', true);
set('parallel_limit', 5);
```

### Task Logging

Configure task logging:

```php
set('log_level', 'info');
set('log_file', '/var/log/deployer.log');
```

## 🎯 Task Best Practices

### Task Organization

1. **Group Related Tasks**: Organize tasks by functionality
2. **Use Descriptive Names**: Make task names clear and descriptive
3. **Add Dependencies**: Specify task dependencies clearly
4. **Handle Errors**: Implement proper error handling in tasks
5. **Add Documentation**: Document complex tasks

### Task Performance

1. **Optimize Task Order**: Arrange tasks for optimal performance
2. **Use Parallel Execution**: Run independent tasks in parallel
3. **Minimize I/O**: Reduce file system operations
4. **Cache Results**: Cache expensive operations
5. **Monitor Performance**: Track task execution times

### Task Security

1. **Validate Inputs**: Validate all task inputs
2. **Use Secure Commands**: Avoid shell injection vulnerabilities
3. **Limit Permissions**: Use minimum required permissions
4. **Log Security Events**: Log security-related operations
5. **Audit Tasks**: Regularly audit custom tasks

## 🎯 Troubleshooting Tasks

### Common Task Issues

1. **Task Timeout**: Increase timeout for long-running tasks
2. **Permission Denied**: Check file permissions and ownership
3. **SSH Connection Failed**: Verify SSH configuration
4. **Database Connection Failed**: Check database credentials
5. **Asset Build Failed**: Verify Node.js and build tools

### Debugging Tasks

```bash
# Run task with verbose output
vendor/bin/dep task_name -v

# Run task with debug output
vendor/bin/dep task_name --debug

# Run task with specific host
vendor/bin/dep task_name hostname
```

## 🎯 Next Steps

- **Read the [Configuration Reference](configuration-reference.md)** - Complete configuration options
- **Explore [Examples](examples/)** - Real-world task examples
- **Check [Best Practices](best-practices.md)** - Task best practices
- **Review [Troubleshooting](troubleshooting.md)** - Common task issues
