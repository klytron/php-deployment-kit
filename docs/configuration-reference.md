# 📋 Configuration Reference

Complete reference for all configuration options available in Klytron Deployer. This guide covers every setting, function, and option you can use to customize your deployment.

## 🎯 Core Configuration Functions

### `klytron_configure_app()`

Configure the basic application settings.

```php
klytron_configure_app(
    string $appName,           // Application name
    string $repository,        // Git repository URL
    array $options = []        // Additional options
);
```

**Parameters:**
- `$appName` (string): Your application name (e.g., 'my-laravel-app')
- `$repository` (string): Git repository URL (e.g., 'git@github.com:user/my-app.git')
- `$options` (array): Additional configuration options

**Available Options:**
```php
[
    'keep_releases' => 3,              // Number of releases to keep (default: 3)
    'default_timeout' => 1800,         // Deployment timeout in seconds (default: 1800)
    'shared_dirs' => [],               // Shared directories (auto-configured)
    'shared_files' => [],              // Shared files (auto-configured)
    'writable_dirs' => [],             // Writable directories (auto-configured)
    'writable_mode' => 'chmod',        // Writable mode: chmod, chown, acl (default: chmod)
    'writable_chmod_mode' => '0755',   // Chmod mode for writable directories
    'writable_chmod_recursive' => true, // Apply chmod recursively
    'writable_use_sudo' => false,      // Use sudo for writable operations
    'cleanup_use_sudo' => false,       // Use sudo for cleanup operations
    'use_relative_symlink' => false,   // Use relative symlinks
    'use_absolute_symlink' => true,    // Use absolute symlinks
    'copy_dirs' => [],                 // Directories to copy instead of symlink
    'clear_paths' => [],               // Paths to clear before deployment
    'clear_use_sudo' => false,         // Use sudo for clear operations
]
```

**Example:**
```php
klytron_configure_app('my-app', 'git@github.com:user/my-app.git', [
    'keep_releases' => 5,
    'default_timeout' => 3600,
    'writable_mode' => 'chown',
]);
```

### `klytron_set_paths()`

Set deployment paths for your application.

```php
klytron_set_paths(
    string $parentDir,         // Parent directory on server
    string $publicHtmlPath     // Public HTML path on server
);
```

**Parameters:**
- `$parentDir` (string): Parent directory (e.g., '/var/www')
- `$publicHtmlPath` (string): Public HTML path (e.g., '/var/www/html')

**Example:**
```php
klytron_set_paths('/var/www', '/var/www/html');
```

#### Multiple public_html aliases (optional)

You can point multiple web roots (domains) to the same deployed application by declaring alias public_html paths. These aliases will be symlinked to the deployed `public_dir_path` during finalize.

```php
// Primary path still comes from klytron_set_paths(...)
// Add one or more additional public_html endpoints:
// Option A: simple paths (ownership falls back to host http_user/http_group, then parent owner)
set('application_public_html_aliases', [
    '/var/www/example1.com/public_html',
    '/var/www/example2.com/public_html',
]);

// Option B: per-alias ownership
set('application_public_html_aliases', [
    ['path' => '/var/www/example1.com/public_html', 'user' => 'www-data', 'group' => 'www-data'],
    ['path' => '/var/www/example2.com/public_html'], // will use host http_user/http_group if set
]);

// Also accepts a single string value
// set('application_public_html_aliases', '/var/www/example.com/public_html');
```

Notes:
- Aliases are processed by the framework‑agnostic task `klytron:deploy:create:server_symlink_aliases` as part of the finalize step.
- Existing directories at alias paths are backed up to a timestamped folder; existing files/symlinks are removed before creation.
- Symlink ownership priority: per‑alias `user:group` → host `http_user:http_group` → parent directory owner:group (Virtualmin suexec‑friendly).
- The source of the symlink is `public_dir_path`. Recipes set this automatically (e.g., Laravel sets `{{deploy_path}}/current/public`). For custom stacks, set it explicitly:

```php
// Example for a simple PHP app (no framework):
set('public_dir_path', '{{deploy_path}}/current');
```

### `klytron_set_domain()`

Set the domain for your application.

```php
klytron_set_domain(string $domain);
```

**Parameters:**
- `$domain` (string): Your domain name (e.g., 'myapp.com')

**Example:**
```php
klytron_set_domain('myapp.com');
```

### `klytron_set_php_version()`

Set the PHP version for your deployment.

```php
klytron_set_php_version(string $phpVersion);
```

**Parameters:**
- `$phpVersion` (string): PHP version (e.g., 'php8.3', 'php8.2', 'php8.1')

**Example:**
```php
klytron_set_php_version('php8.3');
```

## 🎯 Project Configuration

### `klytron_configure_project()`

Configure project-specific settings and capabilities.

```php
klytron_configure_project(array $config);
```

**Available Configuration Options:**

#### Project Type
```php
'type' => 'laravel' | 'yii2' | 'php'
```

#### Database Configuration
```php
'database' => 'mysql' | 'postgresql' | 'sqlite' | 'mariadb' | 'none'
'db_import_path' => 'database/live-db-exports', // Directory containing SQL dumps or SQLite (.sqlite/.db) files
'sqlite_database_path' => '{{deploy_path}}/shared/database/database.sqlite', // Target SQLite database in shared dir
'db_host' => 'localhost',              // Database host (MySQL/PostgreSQL)
'db_port' => 3306,                     // Database port (MySQL/PostgreSQL)
'db_name' => 'myapp',                  // Database name
'db_user' => 'root',                   // Database user
'db_password' => 'password',           // Database password
'db_charset' => 'utf8mb4',             // Database charset
'db_collation' => 'utf8mb4_unicode_ci', // Database collation
```

> **SQLite Deployments:** When `database` is set to `'sqlite'`, `klytron:laravel:deploy:db:import` (or `klytron:laravel:deploy:db:import:sqlite`) discovers `.sqlite` or `.db` files (including encrypted `.sqlite.encrypted` / `.db.encrypted`) in `db_import_path`, puts the app into maintenance mode during copy, replaces `sqlite_database_path`, sets `http_user:http_group` ownership and `664` permissions, and executes `artisan cache:clear`.


#### Environment Files
```php
'env_file_local' => '.env.production',  // Local environment file (false = no env file, skip cleanly)
'env_file_remote' => '.env',            // Remote environment file
```

Projects with no env file at all (cron runners, static tooling) declare
`'env_file_local' => false`: validation and upload both skip cleanly instead
of aborting the plan on a missing file. This is deliberate explicit config,
not a hidden project-type check — `deploy.php` says there is no env file, so
`klytron:validate:env_files` and `klytron:upload:env:production` both return
early on `empty($envFileLocal)` (see `klytron-tasks.php`; CHANGELOG 1.1.11).

#### Consumer-derived task config
```php
'domain_env_var' => 'APP_URL_DOMAIN',   // klytron:set:domain-from-env
'domain_env_file' => '.env.production',
'domain_replace_files' => [             // klytron:deploy:replace-tokens
    '{{release_or_current_path}}/public/loader.php',
],
'domain_replace_search' => 'example.com',
'required_binaries' => ['jpegoptim', 'pngquant'],  // klytron:check:binaries
'decrypt_paths' => [                    // klytron:laravel:decrypt:paths
    '{{release_path}}/resources/00-prod-web',
],
'extra_artisan_commands' => [           // klytron:laravel:extra-commands
    'app:sitemap-generate',
    'storage:link-clean',
],
```

#### Laravel-Specific Options (actual keys in `deployment-kit-core.php`)
```php
'supports_passport' => false,           // Laravel Passport support
'supports_nodejs' => true,              // Node.js build support (generic dispatcher gate)
'supports_vite' => true,                // Vite asset compilation
'supports_mix' => false,                // Laravel Mix support
'supports_filament' => null,            // Filament asset publishing: null = auto-detect, bool = explicit
'supports_storage_link' => true,        // Storage symlink support
'supports_sitemap' => false,            // Sitemap generation gate
'verify_fonts' => false,                // Webfont delivery verification gate
'cleanup_assets' => true,               // Asset mapping + .htaccess cleanup gate
'optimize_images' => false,             // Post-deploy image optimization gate
'enable_encryption' => false,           // Env file encryption (sets env_encryption_environments)
'public_dir_path' => null,              // Override recipe default (e.g. '{{deploy_path}}/current/public')
'shared_dir_path' => null,              // Override recipe default (e.g. '{{deploy_path}}/shared')
```

There are no `supports_queue` / `supports_schedule` / `supports_horizon` /
`supports_telescope`, `supports_rate_limiting` / `supports_api_docs` /
`supports_cors` / `supports_oauth`, `yii2_app_type` / `yii2_apps`,
`backup_*`, or `security_*` keys in `klytron_configure_project()` — the full
key list above is the entire `$defaults` array in
`deployment-kit-core.php`. Backups are driven by the runtime flags
`shouldBackupBeforeDeployment` / `shouldBackupDatabase`
(`set('shouldBackupBeforeDeployment', true)`), not project config.

#### Git & Safety Options
```php
'check_git_pushed' => true,             // Abort if local branch has unpushed commits
```

**Complete Example:**
```php
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    'db_host' => 'localhost',
    'db_name' => 'myapp',
    'db_user' => 'root',
    'db_password' => 'secret',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'supports_vite' => true,
    'supports_storage_link' => true,
    'supports_passport' => false,
    'supports_sitemap' => false,
]);
```

## 🎯 Host Configuration

### `klytron_configure_host()`

Configure server host settings.

```php
klytron_configure_host(
    string $hostname,          // Server hostname
    array $config = []         // Host configuration
);
```

**Available Configuration Options:**

#### Basic Settings
```php
'remote_user' => 'deployer',           // SSH user (default: deployer or DEPLOY_USER env)
'port' => 22,                          // SSH port
'identity_file' => '~/.ssh/id_rsa',    // SSH identity file
'forward_agent' => true,               // Forward SSH agent
'add_keys_to_agent' => true,           // Add keys to SSH agent
'pty' => true,                         // Allocate pseudo-terminal
'keep_forward_agent' => true,          // Keep forward agent
'multiplexing' => true,                // SSH multiplexing
'multiplex_control_path' => '~/.ssh/control-%h-%p-%r', // Control path
'multiplex_control_persist' => '10m',  // Control persist time
```

#### Git Settings
```php
'branch' => 'main',                    // Git branch to deploy
'git_recursive' => true,               // Git recursive submodules
'git_ssh_wrapper' => '',               // Git SSH wrapper
'git_http_credentials' => [],          // Git HTTP credentials
```

#### Web Server Settings
```php
'http_user' => 'www-data',             // Web server user
'http_group' => 'www-data',            // Web server group
'http_method' => 'chmod',              // HTTP method: chmod, chown, acl
'http_chmod_mode' => '0755',           // HTTP chmod mode
'http_chmod_recursive' => true,        // HTTP chmod recursive
'http_use_sudo' => false,              // Use sudo for HTTP operations
```

#### Deployment Settings
```php
'deploy_path' => '/var/www/html',      // Deployment path
'current_path' => '/var/www/html/current', // Current symlink path
'releases_path' => '/var/www/html/releases', // Releases path
'shared_path' => '/var/www/html/shared', // Shared path
'writable_path' => '/var/www/html/writable', // Writable path
'backup_path' => '/var/backups',       // Backup path
'log_path' => '/var/log/deployer',     // Log path
// Multiple-domain support
// Additional public_html endpoints (accepts string paths, or maps with per‑alias owner)
'application_public_html_aliases' => [
    '/var/www/example.com/public_html',
    ['path' => '/var/www/another.com/public_html', 'user' => 'alice', 'group' => 'www-data'],
],
```

#### Labels and Metadata
```php
'labels' => [                          // Host labels
    'stage' => 'production',
    'env' => 'prod',
    'region' => 'us-east-1',
],
'roles' => ['app', 'web', 'db'],       // Host roles
'become' => 'root',                    // Become user
'become_method' => 'sudo',             // Become method
'become_user' => 'root',               // Become user
'become_flags' => '-H -S -n',          // Become flags
```

**Complete Example:**
```php
klytron_configure_host('myapp.com', [
    'remote_user' => 'deploy',
    'port' => 22,
    'identity_file' => '~/.ssh/id_rsa',
    'branch' => 'main',
    'http_user' => 'www-data',
    'http_group' => 'www-data',
    'labels' => [
        'stage' => 'production',
        'env' => 'prod',
    ],
    'roles' => ['app', 'web'],
]);
```

### `klytron_configure_host_from_env()`

Configure host settings dynamically from environment variables, eliminating hardcoded server hostnames, credentials, and branches from your codebase.

```php
klytron_configure_host_from_env(
    string $envVar = 'DEPLOY_HOST',       // Environment variable name for the host
    ?string $fallback = null,             // Fallback hostname when the env var is unset (null = abort with a clear error)
    array $config = []                     // Default host configuration overrides
);
```

**Environment Variables Supported:**
- `DEPLOY_HOST` (or custom name passed in `$envVar`): Server hostname
- `DEPLOY_USER`: Remote SSH user (overrides `$config['remote_user']`)
- `DEPLOY_BRANCH`: Git branch to deploy (overrides `$config['branch']`)
- `DEPLOY_HTTP_USER`: Web server owner (overrides `$config['http_user']`)
- `DEPLOY_HTTP_GROUP`: Web server group (overrides `$config['http_group']`)
- `DEPLOY_PORT`: SSH port (overrides `$config['port']`)

**Example:**
```php
// In deploy.php:
klytron_configure_host_from_env('DEPLOY_HOST', 'your-server.com', [
    'remote_user' => 'deploy',
    'branch'      => 'main',
    'http_user'   => 'www-data',
    'http_group'  => 'www-data',
    'labels'      => ['stage' => 'production'],
]);

// Run via terminal:
// export DEPLOY_HOST=production.example.com
// export DEPLOY_BRANCH=release-v1.1
// vendor/bin/dep deploy
```

## 🎯 Shared Files and Directories

### `klytron_configure_shared_files()`

Configure files that should be shared between releases.

```php
klytron_configure_shared_files(array $files);
```

**Example:**
```php
klytron_configure_shared_files([
    '.env',                    // Environment file
    'public/.htaccess',        // Web server config
    'config/database.php',     // Database config
    'storage/oauth-private.key', // OAuth private key
    'storage/oauth-public.key',  // OAuth public key
]);
```

### `klytron_configure_shared_dirs()`

Configure directories that should be shared between releases.

```php
klytron_configure_shared_dirs(array $directories);
```

**Example:**
```php
klytron_configure_shared_dirs([
    'storage',                 // Application storage
    'public/uploads',          // User uploads
    'public/storage',          // Public storage
    'bootstrap/cache',         // Bootstrap cache
    'logs',                    // Application logs
]);
```

### `klytron_configure_writable_dirs()`

Configure directories that should be writable by the web server.

```php
klytron_configure_writable_dirs(array $directories);
```

**Example:**
```php
klytron_configure_writable_dirs([
    'storage',                 // Storage directory
    'bootstrap/cache',         // Cache directory
    'public/uploads',          // Uploads directory
    'logs',                    // Logs directory
]);
```

## 🎯 Custom Tasks and Hooks

Use standard Deployer functions to add tasks and hooks. There is no separate wrapper — this is intentional so your `deploy.php` calls are portable and Deployer-idiomatic.

### Adding a custom task

```php
task('myproject:warm_cache', function () {
    run('php artisan cache:warm');
})->desc('Warm application cache');
```

### Running a task at a specific point (hooks)

```php
// Syntax: before|after('{existing-task}', '{task-to-run}')
after('deploy:symlink', 'myproject:warm_cache');
before('deploy:vendors', 'myproject:check_secrets');

// Common klytron hook points
after('klytron:laravel:deploy:success', 'klytron:system:restart');  // Reload PHP-FPM
after('deploy:shared', 'klytron:server:deploy:configs');             // Copy server config files
```

### Custom task example

```php
task('myproject:seed', function () {
    run('{{bin/php}} {{release_or_current_path}}/artisan db:seed --force');
})->desc('Run database seeders');

after('klytron:laravel:deploy:database:complete', 'myproject:seed');
```

## 🎯 Environment Variables

There are no `klytron_set_env()` / `klytron_set_env_file()` helpers in this
package (verified: neither exists in `deployment-kit-core.php`). Use native
Deployer `set()` for env-file selection and keep secrets in files, never in
`deploy.php`:

```php
set('env_file_local', '.env.production');  // uploaded by klytron:upload:env:production
set('env_file_remote', '.env');             // target name under shared/
// No env file at all (cron runners, static tooling):
// set('env_file_local', false);
```

Custom tasks likewise use native `task()` / `before()` / `after()` — there is
no `klytron_add_task()` wrapper (see "Custom Tasks and Hooks" above).

## 🎯 Node and Vite Configuration

Canonical task names (verified in `klytron-tasks.php` /
`recipes/klytron-laravel-recipe.php`): `klytron:node:build` (generic
dispatcher), `klytron:laravel:node:vite:build` (Laravel Vite build used by the
Laravel flows), `klytron:laravel:node:mix:build` (Laravel Mix build).
`klytron:node:vite:build` is the framework-agnostic Vite build for non-Laravel
flows — wire the `klytron:laravel:*` names in Laravel `deploy` lists.

### Node/NPM Settings

```php
// Optional tuning for Vite builds
set('npm_cache_dir', '{{deploy_path}}/.npm-cache');                 // NPM cache location
set('npm_registry', 'https://registry.npmjs.org');                   // Primary registry
set('npm_registry_mirror', 'https://registry.npmmirror.com');        // Mirror registry
```

### Vite Settings

```php
// Enable Vite and customize build behavior
set('supports_vite', true);
set('vite_build_command', 'npm run build');
set('vite_env_vars', [
    'APP_NAME', 'APP_ENV', 'APP_URL', 'VITE_PUSHER_APP_KEY', 'VITE_PUSHER_APP_CLUSTER'
]);
```

### Recommended: NVM and .nvmrc

- Install NVM on servers and ensure `$HOME/.nvm/nvm.sh` is present for the deploy user
- Add a project `.nvmrc` specifying your Node version, e.g.:

```
24
```

This ensures the Vite build task activates the intended Node version deterministically.

## 🎯 Database Configuration

### `klytron_configure_database()`

Configure database settings.

```php
klytron_configure_database(string $type, array $config = []);
```

**Parameters:**
- `$type` (string): `'mysql' | 'postgresql' | 'sqlite' | 'mariadb' | 'none'` (`'none'` disables Passport support and skips DB flows)
- `$config` (array): `import_path` (default `'database/live-db-exports'`), `supports_migrations` (default `true`), `supports_seeders` (default `true`) — every key is stored as `database_<key>`

**Example:**
```php
klytron_configure_database('mysql', [
    'import_path' => 'database/live-db-exports',
    'supports_migrations' => true,
    'supports_seeders' => false,
]);
```

## 🎯 Backup Configuration

There is no `klytron_configure_backup()` helper in this package (verified: it
does not exist in `deployment-kit-core.php`). Backups are driven by native
Deployer runtime flags (defaults in `deployment-kit-core.php`):

```php
set('shouldBackupBeforeDeployment', true);  // default false; gates klytron:*:backup:create in prepare flows
set('shouldBackupDatabase', true);          // default true; mysql/mariadb via MYSQL_PWD, postgres via PGPASSWORD
set('backup_path', '{{deploy_path}}/backups'); // default; backup_keep defaults to 5
```

## 🎯 Security Configuration

There is no `klytron_configure_security()` helper in this package. The
equivalent guards are individual keys / tasks:

```php
set('check_git_pushed', true); // klytron:deploy:check_pushed aborts on unpushed commits
// klytron:validate:remote_user warns on remote_user === 'root'
// (default remote_user is 'deployer', honoring DEPLOY_USER; sudoers guide:
// docs/quick-start.md#non-root-deployment-and-sudoers-setup)
```

## 🎯 Runtime Tuning

All keys are plain Deployer config — set them with native `set()`:

| Key | Default | Used by |
|---|---|---|
| `skip_opcache_reset` | `false` | `klytron:opcache:reset` — `true` disables the SAPI reset |
| `health_check_timeout` | `15` (seconds) | `klytron:deploy:health_check` curl `--max-time` |
| `health_check_expected_code` | `200` (`301`/`302` also pass when `200` is expected) | `klytron:deploy:health_check` |
| `plan_target_task` | `'deploy'` | `klytron:plan` resolves the task graph for this task instead |
| `default_file_permissions` | `0644` | `klytron:deploy:access_permissions` file mode |
| `default_dir_permissions` | `0755` (setgid bit OR-ed → `2755`) | `klytron:deploy:access_permissions` dir mode |
| `laravel_storage_permissions` | `0775` | `klytron:deploy:laravel:access_permissions` + generic sweep on `storage` |
| `laravel_cache_permissions` | `0775` | same, on `bootstrap/cache` |
| `debug_env_upload` | `false` | `klytron:upload:env:production` masked preview via `klytron_mask_secrets()` |
| `system_reboot_on_deploy` | `false` | `klytron:system:restart` — `true` reboots instead of `klytron:fpm:reload` |
| `temp_dir` | `sys_get_temp_dir()` | Staging dir for the masked `.env` download during Vite builds |

```php
set('skip_opcache_reset', true);        // systemd-only hosts, no Virtualmin FCGI
set('health_check_timeout', 30);
set('health_check_expected_code', 200);
set('plan_target_task', 'deploy');
set('default_file_permissions', 0644);
set('default_dir_permissions', 0755);
set('laravel_storage_permissions', 0775);
set('laravel_cache_permissions', 0775);
set('debug_env_upload', false);         // true only for local debugging — preview is masked but keep it off
set('system_reboot_on_deploy', false);
```

## 🎯 Unattended CI (`klytron:laravel:init:questions`)

Each key answers one interactive prompt; `null` (the default) means "ask".
Set them all for fully non-interactive CI:

| Key | Values (`null` = prompt) |
|---|---|
| `auto_confirm_production` | `true` / `false` — deploy to production? |
| `auto_deployment_type` | `'update'` / `'fresh'` |
| `auto_upload_env` | `true` / `false` — upload `.env` (fresh installs) |
| `auto_database_operation` | `'migrations'` / `'import'` / `'both'` / `'none'` |
| `auto_clear_caches` | `true` / `false` |
| `auto_confirm_settings` | `true` / `false` — skip final confirmation |

```php
// deploy.php (CI): answer every prompt up front, null anywhere = ask
set('auto_confirm_production', true);
set('auto_deployment_type', 'update');
set('auto_upload_env', false);
set('auto_database_operation', 'migrations');
set('auto_clear_caches', true);
set('auto_confirm_settings', true);
```

## 🎯 Complete Configuration Example

Full Laravel project deploy.php:

```php
<?php
namespace Deployer;

require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';
require __DIR__ . '/vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

klytron_configure_app('my-laravel-app', 'git@github.com:my-org/my-app.git', [
    'keep_releases'   => 3,
    'default_timeout' => 1800,
]);

klytron_set_paths('/var/www', '/var/www/${APP_URL_DOMAIN}/public_html');
klytron_set_domain('myapp.com');
klytron_set_php_version('php8.3');

klytron_configure_project([
    'type'                  => 'laravel',
    'database'              => 'mysql',
    'env_file_local'        => '.env.production',
    'env_file_remote'       => '.env',
    'supports_nodejs'       => true,
    'supports_vite'         => true,
    'supports_storage_link' => true,
    'supports_passport'     => false,
    'supports_sitemap'      => true,
    'verify_fonts'          => true,
    'cleanup_assets'        => true,
]);

klytron_configure_host('myapp.com', [
    'remote_user' => 'deploy',
    'branch'      => 'main',
    'http_user'   => 'www-data',
    'http_group'  => 'www-data',
    'labels'      => ['stage' => 'production'],
    'ssh_options' => ['ConnectTimeout' => 30, 'ServerAliveInterval' => 60, 'ServerAliveCountMax' => 3],
]);

klytron_configure_shared_files(['.env']);
klytron_configure_shared_dirs(['storage', 'public/storage', 'bootstrap/cache']);
klytron_configure_writable_dirs([
    'bootstrap/cache', 'storage', 'storage/app', 'storage/app/public',
    'storage/framework', 'storage/framework/cache', 'storage/framework/sessions',
    'storage/framework/views', 'storage/logs', 'public/storage',
]);

set('server_config_files', [
    ['source' => 'server/.htaccess.production', 'target' => 'public/.htaccess', 'mode' => 0644, 'overwrite' => true],
]);

task('deploy', [
    'klytron:deploy:start_timer',
    'deploy:unlock',
    'klytron:deploy:fix_repo',
    'klytron:laravel:deploy:prepare:complete',
    'deploy:setup', 'deploy:lock', 'deploy:release', 'deploy:update_code',
    'deploy:shared', 'klytron:deploy:fix_git_ownership',
    'klytron:laravel:deploy:environment:complete',
    'deploy:vendors',
    'klytron:laravel:node:vite:build',
    'klytron:laravel:deploy:database:complete',
    'deploy:writable',
    'klytron:laravel:deploy:cache:complete',
    'deploy:symlink',
    'klytron:laravel:deploy:finalize:complete',
    'klytron:assets:map', 'klytron:assets:cleanup',
    'klytron:sitemap:generate', 'klytron:sitemap:verify', 'klytron:sitemap:check',
    'klytron:fonts:verify', 'klytron:images:optimize',
    'deploy:unlock', 'deploy:cleanup',
    'klytron:deploy:access_permissions',
    'klytron:laravel:deploy:notify:complete',
    'klytron:deploy:end_timer',
])->desc('Deploy to production');

// PHP-FPM restart is already hooked automatically to klytron:laravel:deploy:success by the recipe
// Local environment file (.env.production) is validated automatically by klytron:validate:basic
after('deploy:shared', 'klytron:server:deploy:configs');
```

## 🎯 Configuration Best Practices

1. **Use Environment-Specific Files**: Keep different `.env` files for different environments
2. **Secure Sensitive Data**: Never commit passwords or API keys to version control
3. **Use SSH Keys**: Configure SSH key authentication for secure deployments
4. **Enable Backups**: Always enable backups for production deployments
5. **Test Configuration**: Use `vendor/bin/dep test` to validate your configuration
6. **Use Labels**: Add meaningful labels to your hosts for better organization
7. **Limit Permissions**: Use the minimum required permissions for web server users
8. **Monitor Deployments**: Use verbose output (`-v`) for debugging deployments

## 🎯 Next Steps

- **Read the [Function Reference](function-reference.md)** - Complete function documentation
- **Explore [Examples](examples/)** - Real-world configuration examples
- **Check [Best Practices](best-practices.md)** - Configuration best practices
- **Review [Troubleshooting](troubleshooting.md)** - Common configuration issues
