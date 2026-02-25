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
'db_host' => 'localhost',              // Database host
'db_port' => 3306,                     // Database port
'db_name' => 'myapp',                  // Database name
'db_user' => 'root',                   // Database user
'db_password' => 'password',           // Database password
'db_charset' => 'utf8mb4',             // Database charset
'db_collation' => 'utf8mb4_unicode_ci', // Database collation
```

#### Environment Files
```php
'env_file_local' => '.env.production',  // Local environment file
'env_file_remote' => '.env',            // Remote environment file
'env_backup_enabled' => true,           // Enable environment backup
```

#### Laravel-Specific Options
```php
'supports_passport' => false,           // Laravel Passport support
'supports_nodejs' => false,             // Node.js build support (generic)
'supports_vite' => false,               // Vite asset compilation
'supports_mix' => false,                // Laravel Mix support
'supports_storage_link' => true,        // Storage symlink support
'supports_sitemap' => false,            // Sitemap generation
'supports_queue' => false,              // Queue worker support
'supports_schedule' => false,           // Task scheduler support
'supports_horizon' => false,            // Laravel Horizon support
'supports_telescope' => false,          // Laravel Telescope support
```

#### API-Specific Options
```php
'supports_rate_limiting' => false,      // Rate limiting support
'supports_api_docs' => false,           // API documentation
'supports_cors' => false,               // CORS support
'supports_oauth' => false,              // OAuth support
```

#### Yii2-Specific Options
```php
'yii2_app_type' => 'advanced',          // Yii2 app type: basic, advanced
'yii2_apps' => ['frontend', 'backend'], // Yii2 applications
'supports_maintenance' => true,         // Maintenance mode support
```

#### Backup Configuration
```php
'backup_enabled' => true,               // Enable backups
'backup_database' => true,              // Backup database
'backup_files' => false,                // Backup files
'backup_keep_days' => 7,                // Keep backups for days
'backup_path' => '/var/backups',        // Backup path
```

#### Security Options
```php
'security_checks' => true,              // Enable security checks
'env_validation' => true,               // Validate environment
'ssh_key_validation' => true,           // Validate SSH keys
'production_safety' => true,            // Production safety checks
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
    'backup_enabled' => true,
    'backup_database' => true,
    'security_checks' => true,
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
'remote_user' => 'root',               // SSH user
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
    'remote_user' => 'root',
    'port' => 22,
    'identity_file' => '~/.ssh/id_rsa',
    'branch' => 'main',
    'http_user' => 'www-data',
    'http_group' => 'www-data',
    'deploy_path' => '/var/www/html',
    'labels' => [
        'stage' => 'production',
        'env' => 'prod',
    ],
    'roles' => ['app', 'web'],
]);
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

### `klytron_set_env()`

Set environment variables for deployment.

```php
klytron_set_env(string $key, string $value);
```

**Example:**
```php
klytron_set_env('APP_ENV', 'production');
klytron_set_env('APP_DEBUG', 'false');
klytron_set_env('CACHE_DRIVER', 'redis');
```

### `klytron_set_env_file()`

Set environment file path.

```php
klytron_set_env_file(string $localFile, string $remoteFile);
```

**Example:**
```php
klytron_set_env_file('.env.production', '.env');
```

## 🎯 Node and Vite Configuration

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
klytron_configure_database(array $config);
```

**Available Options:**
```php
[
    'type' => 'mysql' | 'postgresql' | 'sqlite' | 'mariadb',
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'myapp',
    'user' => 'root',
    'password' => 'secret',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'migrations' => true,              // Run migrations
    'seeds' => false,                  // Run seeders
    'backup_before_migrate' => true,   // Backup before migration
    'migration_table' => 'migrations', // Migration table name
]
```

**Example:**
```php
klytron_configure_database([
    'type' => 'mysql',
    'host' => 'localhost',
    'name' => 'myapp',
    'user' => 'root',
    'password' => 'secret',
    'migrations' => true,
    'backup_before_migrate' => true,
]);
```

## 🎯 Backup Configuration

### `klytron_configure_backup()`

Configure backup settings.

```php
klytron_configure_backup(array $config);
```

**Available Options:**
```php
[
    'enabled' => true,                 // Enable backups
    'database' => true,                // Backup database
    'files' => false,                  // Backup files
    'keep_days' => 7,                  // Keep backups for days
    'path' => '/var/backups',          // Backup path
    'compress' => true,                // Compress backups
    'encrypt' => false,                // Encrypt backups
    'notify' => false,                 // Notify on backup
    'before_deploy' => true,           // Backup before deployment
    'after_deploy' => false,           // Backup after deployment
]
```

**Example:**
```php
klytron_configure_backup([
    'enabled' => true,
    'database' => true,
    'keep_days' => 7,
    'path' => '/var/backups',
    'before_deploy' => true,
]);
```

## 🎯 Security Configuration

### `klytron_configure_security()`

Configure security settings.

```php
klytron_configure_security(array $config);
```

**Available Options:**
```php
[
    'checks' => true,                  // Enable security checks
    'env_validation' => true,          // Validate environment
    'ssh_key_validation' => true,      // Validate SSH keys
    'production_safety' => true,       // Production safety checks
    'confirm_destructive' => true,     // Confirm destructive operations
    'validate_permissions' => true,    // Validate file permissions
    'check_php_version' => true,       // Check PHP version
    'check_extensions' => true,        // Check PHP extensions
]
```

**Example:**
```php
klytron_configure_security([
    'checks' => true,
    'env_validation' => true,
    'production_safety' => true,
    'confirm_destructive' => true,
]);
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

after('klytron:laravel:deploy:success', 'klytron:system:restart');
after('deploy:shared', 'klytron:server:deploy:configs');

if (!file_exists('.env.production')) {
    throw new \RuntimeException('.env.production is required.');
}
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
