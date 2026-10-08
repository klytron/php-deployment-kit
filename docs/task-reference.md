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

### `deploy:database:backup`

Create database backup.

```bash
vendor/bin/dep deploy:database:backup
```

**What it does:**
- Create database backup
- Compress backup file
- Store backup in configured location

### `deploy:database:migrate`

Run database migrations.

```bash
vendor/bin/dep deploy:database:migrate
```

**What it does:**
- Run framework-specific migrations
- Handle migration errors
- Log migration results

### `deploy:database:seed`

Run database seeders.

```bash
vendor/bin/dep deploy:database:seed
```

**What it does:**
- Run database seeders
- Seed production data
- Handle seeding errors

### `deploy:database:import`

Import database from file.

```bash
vendor/bin/dep deploy:database:import
```

**What it does:**
- Import database from SQL file
- Handle import errors
- Validate import results

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

### `test`

Test deployment configuration.

```bash
vendor/bin/dep test
```

**What it does:**
- Validate configuration
- Test SSH connectivity
- Check server requirements
- Verify file permissions

### `test:ssh`

Test SSH connectivity.

```bash
vendor/bin/dep test:ssh
```

**What it does:**
- Test SSH connection to hosts
- Verify SSH key authentication
- Check SSH configuration

### `test:database`

Test database connectivity.

```bash
vendor/bin/dep test:database
```

**What it does:**
- Test database connection
- Verify database credentials
- Check database permissions

### `test:env`

Test environment configuration.

```bash
vendor/bin/dep test:env
```

**What it does:**
- Validate environment files
- Check environment variables
- Verify configuration settings

## 🎯 Utility Tasks

### `current`

Show current release information.

```bash
vendor/bin/dep current
```

**What it does:**
- Display current release
- Show release timestamp
- List release details

### `releases`

List all releases.

```bash
vendor/bin/dep releases
```

**What it does:**
- List all releases
- Show release timestamps
- Display release sizes

### `status`

Show deployment status.

```bash
vendor/bin/dep status
```

**What it does:**
- Show deployment status
- Display host information
- List active releases

### `rollback`

Rollback to previous release.

```bash
vendor/bin/dep rollback
```

**What it does:**
- Rollback to previous release
- Update symlinks
- Clean up current release

### `rollback:list`

List available rollback targets.

```bash
vendor/bin/dep rollback:list
```

**What it does:**
- List available releases
- Show rollback options
- Display release information

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
# Backup database
vendor/bin/dep deploy:database:backup

# Run migrations
vendor/bin/dep deploy:database:migrate

# Seed database
vendor/bin/dep deploy:database:seed
```

### Asset Building

```bash
# Build all assets
vendor/bin/dep deploy:assets:build

# Build Vite assets
vendor/bin/dep deploy:assets:vite

# Build Mix assets
vendor/bin/dep deploy:assets:mix
```

### Testing and Validation

```bash
# Test configuration
vendor/bin/dep test

# Test SSH connectivity
vendor/bin/dep test:ssh

# Test database connection
vendor/bin/dep test:database
```

### Utility Operations

```bash
# Show current release
vendor/bin/dep current

# List all releases
vendor/bin/dep releases

# Show deployment status
vendor/bin/dep status

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
| `klytron:check:binaries` | `required_binaries` (list) | Per-project optimizer/tool presence warnings; warning-only, never fails |
| `klytron:laravel:decrypt:paths` | `decrypt_paths` (release-relative dirs) | Per-project `artisan file:decrypt <dir>` loops |
| `klytron:laravel:extra-commands` | `extra_artisan_commands` (verbatim command strings) | Per-project sitemap/link-fixup tasks — wire once in the flow |
| `klytron:laravel:check:web-php` | none (reads local `composer.json` + live URL) | Per-project "is the domain PHP new enough" warnings |

Wire the flow-point tasks in the project's `deploy` list (e.g.
`extra-commands` after finalize, `check:web-php` at the end); the rest run
standalone or wherever the flow needs them.

### `klytron:deploy:health_check`

Automated HTTP verification task that ensures the application is live and returning HTTP 200 post-symlink.

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
