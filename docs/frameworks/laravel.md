# 🚀 Laravel Deployment Guide

Complete guide for deploying Laravel applications with Klytron Deployer. This guide covers Laravel-specific features, best practices, and configuration options.

## 🎯 Laravel Features

Klytron Deployer provides comprehensive support for Laravel applications:

- **Artisan Commands** - Automatic execution of Laravel Artisan commands
- **Database Migrations** - Safe database migration handling
- **Cache Management** - Automatic cache clearing and optimization
- **Storage Configuration** - Storage symlink and permission management
- **Environment Files** - Secure environment file handling
- **Asset Compilation** - Vite and Mix asset building
- **Queue Management** - Queue worker and job management
- **Passport Support** - Laravel Passport OAuth configuration
- **Optimization** - Laravel optimization commands

## 🎯 Quick Start for Laravel

### 1. Install Klytron Deployer

```bash
composer require klytron/php-deployment-kit
```

### 2. Copy Laravel Template

```bash
cp vendor/klytron/php-deployment-kit/templates/laravel-deploy.php.template deploy.php
```

### 3. Configure Your Laravel Application

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Configure Laravel application
klytron_configure_app('my-laravel-app', 'git@github.com:user/my-laravel-app.git');

// Set deployment paths
klytron_set_paths('/var/www', '/var/www/html');

// Configure Laravel project
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    'supports_vite' => true,
    'supports_storage_link' => true,
    'supports_passport' => false,
]);

// Configure host
klytron_configure_host('myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
    'http_group' => 'www-data',
]);

// Optional: serve the same app on multiple domains
// Simple aliases (ownership falls back to host http_user/http_group)
set('application_public_html_aliases', [
    '/var/www/alias1.com/public_html',
    '/var/www/alias2.com/public_html',
]);

// Or with per-alias ownership
set('application_public_html_aliases', [
    ['path' => '/var/www/alias1.com/public_html', 'user' => 'deploy', 'group' => 'deploy'],
    ['path' => '/var/www/alias2.com/public_html'], // uses host http_user/http_group
]);
```

### 4. Deploy

```bash
vendor/bin/dep deploy
```

## 🎯 Laravel Configuration Options

### Basic Laravel Configuration

```php
klytron_configure_project([
    'type' => 'laravel',                    // Laravel project type
    'database' => 'mysql',                  // Database type: mysql, postgresql, sqlite
    'env_file_local' => '.env.production',  // Local environment file
    'env_file_remote' => '.env',            // Remote environment file
    'supports_storage_link' => true,        // Enable storage symlink
    'supports_passport' => false,           // Enable Passport support
    'supports_nodejs' => false,             // Enable Node.js builds
    'supports_vite' => true,                // Enable Vite asset compilation (with hardlink cache)
    'supports_filament' => true,            // Publish Filament v5 assets (php artisan filament:assets)
    'supports_mix' => false,                // Enable Mix support
    'check_git_pushed' => true,             // Abort if unpushed commits exist locally
]);
```

### Advanced Laravel Configuration

```php
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'supports_vite' => true,
    'supports_storage_link' => true,
    'supports_passport' => true,
]);

// Database credentials are plain Deployer config (note `db_pass`), not
// project flags:
set('db_host', 'localhost');
set('db_name', 'myapp');
set('db_user', 'root');
set('db_pass', 'secret');

// Extra artisan commands run verbatim via klytron:laravel:extra-commands:
set('extra_artisan_commands', [
    'config:cache',
    'route:cache',
    'view:cache',
    'queue:restart',
]);
```

## 🎯 Laravel-Specific Tasks

### Available Laravel Tasks

The `task('deploy:laravel:*', ...)` examples further below are per-project
starting points you own — the kit tasks above are what actually ships.
Custom values they read via `get()` must be stored with `set()` (unknown
keys passed to `klytron_configure_project()` are dropped).

#### `klytron:laravel:deploy:environment:complete`

Upload the Laravel environment file (plus optional decryption).

```bash
vendor/bin/dep klytron:laravel:deploy:environment:complete
```

#### `klytron:laravel:storage:link`

Create the Laravel storage symlink.

```bash
vendor/bin/dep klytron:laravel:storage:link
```

#### `klytron:laravel:deploy:cache:complete`

Clear and rebuild Laravel caches (`cache:clear`, `config:cache`, `optimize`).

```bash
vendor/bin/dep klytron:laravel:deploy:cache:complete
```

#### `klytron:laravel:deploy:db:migrate`

Run Laravel database migrations.

```bash
vendor/bin/dep klytron:laravel:deploy:db:migrate
```

#### Seeders

There is no standalone seeder task — run seeders through the
consumer-derived extra-commands task:

```php
set('extra_artisan_commands', ['db:seed --force']);
```

```bash
vendor/bin/dep klytron:laravel:extra-commands
```

#### `klytron:laravel:deploy:passport:install`

Configure Laravel Passport OAuth.

```bash
vendor/bin/dep klytron:laravel:deploy:passport:install
```

#### `klytron:laravel:deploy:cache:complete` (optimize)

Application optimization (`artisan:optimize` from Deployer's core Laravel
recipe) runs inside the cache task above — there is no separate optimize
task.

```bash
vendor/bin/dep klytron:laravel:deploy:cache:complete
```

## 🎯 Laravel Environment Configuration

### Environment File Setup

```php
// Configure environment files (real keys only)
klytron_configure_project([
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
]);

// Configure shared files to include .env
klytron_configure_shared_files([
    '.env',
    'public/.htaccess',
]);
```

### Environment Variables

Set Laravel-specific environment variables:

```php
// Set Laravel environment variables
set('APP_ENV', 'production');
set('APP_DEBUG', 'false');
set('APP_URL', 'https://myapp.com');
set('LOG_CHANNEL', 'stack');
set('CACHE_DRIVER', 'redis');
set('SESSION_DRIVER', 'redis');
set('QUEUE_CONNECTION', 'redis');
```

## 🎯 Laravel Database Management

### Database Configuration

```php
// Configure database (real signature: string $type first, then options)
klytron_configure_database('mysql', [
    'import_path' => 'database/live-db-exports',
    'supports_migrations' => true,
    'supports_seeders' => true,
]);

// Credentials for dumps are plain Deployer config (note `db_pass`):
set('db_host', 'localhost');
set('db_port', 3306);
set('db_name', 'myapp');
set('db_user', 'root');
set('db_pass', 'secret');
```

### Database Migrations

```php
// Migrations run via klytron:laravel:deploy:db:migrate whenever the
// shouldRunMigration flag is true (asked interactively when unset):
set('shouldRunMigration', true);
set('shouldBackupBeforeDeployment', true); // snapshot current release first
```

### Database Seeders

```php
// Run seeders through the consumer-derived extra-commands task:
set('extra_artisan_commands', ['db:seed --force']);
```

```bash
vendor/bin/dep klytron:laravel:extra-commands
```

## 🎯 Laravel Asset Management

### Vite Asset Compilation

The package provides intelligent Vite asset compilation with automatic Node.js detection and fallback to local builds when needed.

```php
// Configure Vite support (the kit task runs `npm run build` — there are no
// command-override keys, only the enable/disable flags)
klytron_configure_project([
    'supports_vite' => true,
]);
```

### Understanding Node.js Build Configuration

The deployment system uses two key configuration options to control asset building:

#### 1. `supports_nodejs` - Enable/Disable Node.js Builds

This setting controls whether the project supports Node.js-based asset compilation:

```php
klytron_configure_project([
    'supports_nodejs' => true,   // Project supports Node.js builds
    // OR
    'supports_nodejs' => false,  // Project does NOT support Node.js (no build attempted)
]);
```

- **`supports_nodejs: true`** - The project supports Node.js builds. The deployment will attempt to detect Node.js on the server.
- **`supports_nodejs: false`** - The project does not support Node.js. The build task will be skipped entirely.

#### 2. `supports_vite` - Enable Vite Build

This setting enables the Vite build system specifically:

```php
klytron_configure_project([
    'supports_vite' => true,    // Enable Vite asset compilation
    // OR
    'supports_vite' => false,   // Disable Vite (for pre-built assets)
]);
```

### How the Build System Works

The `klytron:laravel:node:vite:build` task implements intelligent detection:

```
┌─────────────────────────────────────────────────────────────┐
│ klytron:laravel:node:vite:build                             │
├─────────────────────────────────────────────────────────────┤
│ 1. Check supports_nodejs setting                            │
│    ├─ supports_nodejs = false                               │
│    │   └─ Skip build entirely (no assets)                  │
│    └─ supports_nodejs = true                                │
│        │                                                    │
│  2. Detect Node.js on remote server                        │
│    ├─ Node.js FOUND on server                              │
│    │   └─ Build assets on server                           │
│    └─ Node.js NOT FOUND on server                          │
│        └─ Fallback to local build + upload                 │
└─────────────────────────────────────────────────────────────┘
```

#### Build Flow Details:

1. **If `supports_nodejs = false`**:
   - Task skips entirely
   - No build attempted
   - Suitable for projects without Node.js/Vite

2. **If `supports_nodejs = true`**:
   - System detects Node.js on remote server
   - **If Node.js is available**: Builds assets directly on server
   - **If Node.js is NOT available**: Falls back to local build (`klytron:laravel:node:vite:build:local`)

### Local Build Fallback

When Node.js is not available on the remote server, the system automatically:

1. Builds assets locally using `npm run build`
2. Uploads the built assets to the server
3. Continues deployment seamlessly

This is handled by the `klytron:laravel:node:vite:build:local` task:

```php
task('klytron:laravel:node:vite:build:local', function () {
    info("🏗️ Building frontend assets locally...");
    runLocally('npm ci || npm install');
    runLocally('npm run build');

    info("📤 Uploading built assets...");
    upload('public/build/', '{{release_path}}/public/build/');
    info("✅ Built assets uploaded");
})->desc('Build Vite assets locally and upload');
```

### Node/NVM Recommendations for Laravel

- Install NVM on deployment servers (`$HOME/.nvm/nvm.sh` available for deploy user)
- Add `.nvmrc` in project root with your Node version (e.g., `24`)
- The Vite task auto-activates Node via NVM when `node` is not in PATH and respects `.nvmrc`
- Keep `supports_vite` enabled only if you compile assets during deploy; otherwise pre-build and disable

### Project Configuration Examples

#### Example 1: Project WITH Node.js on Server

```php
klytron_configure_project([
    'supports_nodejs' => true,    // Project supports Node.js
    'supports_vite' => true,      // Use Vite for asset building
]);

// Result: Builds on server if Node.js is available
```

#### Example 2: Project WITHOUT Node.js (Pre-built Assets)

```php
klytron_configure_project([
    'supports_nodejs' => false,   // No Node.js support
    'supports_vite' => false,     // No Vite
]);

// Pre-build assets locally and commit to git
// Result: Skips build entirely
```

#### Example 3: Server Without Node.js (Build Locally)

```php
klytron_configure_project([
    'supports_nodejs' => true,    // Project supports Node.js
    'supports_vite' => true,      // Use Vite
]);

// Server doesn't have Node.js installed
// Result: Automatically builds locally and uploads
```

### Using in Deployment Flow

In your `deploy.php`, use the main build task that handles all detection:

```php
task('deploy', [
    // ... other tasks ...
    'klytron:laravel:node:vite:build',  // Auto-detects and handles everything
    // ... other tasks ...
])->desc('Deploy application');
```

The task automatically:

- Checks if Node.js is supported (`supports_nodejs`)
- Detects Node.js on the remote server
- Builds on server OR falls back to local build

### Laravel Mix Asset Compilation

```php
// Configure Mix support (mix_build_command is read by
// klytron:laravel:node:mix:build, default 'npm run production')
klytron_configure_project([
    'supports_mix' => true,
]);
set('mix_build_command', 'npm run production');
```

### Asset Optimization

```php
// Configure asset handling (real kit flags only)
klytron_configure_project([
    'supports_vite' => true,
    'cleanup_assets' => true,  // remove rogue .htaccess from build output
    'optimize_images' => true, // compress images in storage/app/public/
]);
// CDN URLs and compression headers are application config — set ASSET_URL in
// your .env file and configure them on your web server / CDN, not in kit config.
```

## 🎯 Laravel Storage Configuration

### Storage Symlink

```php
// Enable storage symlink
klytron_configure_project([
    'supports_storage_link' => true,
]);

// Configure shared directories
klytron_configure_shared_dirs([
    'storage',
    'public/uploads',
    'public/storage',
    'bootstrap/cache',
]);
```

### Storage Permissions

```php
// Configure writable directories
klytron_configure_writable_dirs([
    'storage',
    'bootstrap/cache',
    'public/uploads',
]);
```

## 🎯 Laravel Cache Management

### Cache Configuration

```php
// Cache clearing on deploy is handled by the kit task
// klytron:laravel:deploy:cache:complete (runs when shouldClearAllCaches is
// true, the default). Drivers themselves are application config — put them
// in your .env file (CACHE_DRIVER=redis, SESSION_DRIVER=redis).
set('shouldClearAllCaches', true);
```

### Cache Optimization

```php
// Cache optimization runs inside klytron:laravel:deploy:cache:complete
// (config:cache + optimize). No kit flags are needed — wire that task into
// your deploy flow (see quick-start.md).
```

## 🎯 Laravel Queue Management

### Queue Configuration

```php
// Queue workers are application config, not kit flags. Restart workers
// through the consumer-derived extra-commands task:
set('extra_artisan_commands', ['queue:restart']);
```

### Queue Workers

```php
// Configure queue workers
task('deploy:laravel:queue', function () {
    run('php artisan queue:restart');
    run('php artisan queue:work --daemon --sleep=3 --tries=3');
})->desc('Restart Laravel queue workers');
```

## 🎯 Laravel Passport Configuration

### Passport Setup

```php
// Configure Passport support
klytron_configure_project([
    'supports_passport' => true,
]);

// Configure shared files for Passport keys
klytron_configure_shared_files([
    '.env',
    'storage/oauth-private.key',
    'storage/oauth-public.key',
]);
```

### Passport Installation

```php
// Add Passport installation task (custom example — the kit ships
// klytron:laravel:deploy:passport:install, prefer that task)
task('deploy:laravel:passport_install', function () {
    run('php artisan passport:install');
})->desc('Install Laravel Passport');
```

## 🎯 Laravel Maintenance Mode

### Maintenance Mode Configuration

```php
// Maintenance mode has no kit flags — store your own values with set()
// (read by the custom tasks below via get() with defaults):
set('maintenance_message', 'Deploying...');
set('maintenance_retry', 60);
```

### Maintenance Mode Tasks

```php
// Add maintenance mode tasks
task('deploy:laravel:maintenance_on', function () {
    $message = get('maintenance_message', 'Deploying...');
    $retry = get('maintenance_retry', 60);
    run("php artisan down --message='{$message}' --retry={$retry}");
})->desc('Enable Laravel maintenance mode');

task('deploy:laravel:maintenance_off', function () {
    run('php artisan up');
})->desc('Disable Laravel maintenance mode');
```

## 🎯 Laravel Optimization

### Application Optimization

```php
// Application optimization runs inside klytron:laravel:deploy:cache:complete
// (cache:clear + config:cache + optimize). No kit flags are needed — wire
// that task into your deploy flow (see quick-start.md).
```

### Performance Optimization

```php
// Add optimization tasks (custom example — cache/config optimization already
// runs inside klytron:laravel:deploy:cache:complete, prefer that task)
task('deploy:laravel:optimize', function () {
    run('php artisan config:cache');
    run('php artisan route:cache');
    run('php artisan view:cache');
    run('composer dump-autoload --optimize');
})->desc('Optimize Laravel application');
```

## 🎯 Laravel Health Checks

### Application Health Checks

```php
// Add Laravel health checks (custom example — the kit ships
// klytron:deploy:health_check, prefer that task)
task('deploy:laravel:health_check', function () {
    $healthChecks = [
        'Application accessible' => 'curl -f http://localhost/health || exit 1',
        'Database connection' => 'php artisan migrate:status',
        'Cache working' => 'php artisan about',
        'Queue working' => 'php artisan queue:failed',
        'Storage accessible' => 'php artisan storage:link',
    ];

    foreach ($healthChecks as $check => $command) {
        try {
            writeln("<info>Checking: {$check}</info>");
            run($command);
            writeln("<info>✓ {$check} passed</info>");
        } catch (Exception $e) {
            writeln("<error>✗ {$check} failed: " . $e->getMessage() . "</error>");
            throw $e;
        }
    }
})->desc('Run Laravel health checks');
```

## 🎯 Laravel Deployment Examples

### Basic Laravel Deployment

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Basic Laravel configuration
klytron_configure_app('my-laravel-app', 'git@github.com:user/my-laravel-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('myapp.com');

klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    'supports_storage_link' => true,
]);

klytron_configure_host('myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);
```

### Advanced Laravel Deployment

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Advanced Laravel configuration
klytron_configure_app('my-advanced-laravel-app', 'git@github.com:user/my-laravel-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('myapp.com');

klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'supports_vite' => true,
    'supports_storage_link' => true,
    'supports_passport' => true,
]);

// Database credentials are plain Deployer config (note `db_pass`), not
// project flags. Cache/queue drivers are application config — put them in
// your .env file (CACHE_DRIVER=redis, QUEUE_CONNECTION=redis).
set('db_host', 'localhost');
set('db_name', 'myapp');
set('db_user', 'root');
set('db_pass', 'secret');

klytron_configure_host('myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
    'http_group' => 'www-data',
]);

// Configure shared files and directories
klytron_configure_shared_files([
    '.env',
    'storage/oauth-private.key',
    'storage/oauth-public.key',
]);

klytron_configure_shared_dirs([
    'storage',
    'public/uploads',
    'public/storage',
    'bootstrap/cache',
]);

klytron_configure_writable_dirs([
    'storage',
    'bootstrap/cache',
    'public/uploads',
]);
```

### Laravel API Deployment

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Laravel API configuration
klytron_configure_app('my-laravel-api', 'git@github.com:user/my-laravel-api.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('api.myapp.com');

klytron_configure_project([
    'type' => 'laravel',
    'database' => 'postgresql',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'supports_passport' => true,
    'supports_vite' => false,
]);

// Database credentials are plain Deployer config (note `db_pass`), not
// project flags. Cache/session drivers are application config — put them in
// your .env file.
set('db_host', 'localhost');
set('db_name', 'myapp_api');
set('db_user', 'postgres');
set('db_pass', 'secret');

klytron_configure_host('api.myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);
```

## 🎯 Laravel Best Practices

### Security Best Practices

1. **Environment Files**: Never commit `.env` files to version control
2. **Application Key**: Ensure `APP_KEY` is set in production
3. **Debug Mode**: Set `APP_DEBUG=false` in production
4. **HTTPS**: Use HTTPS in production with proper SSL configuration
5. **File Permissions**: Set proper file permissions for storage and cache directories

### Performance Best Practices

1. **Cache Configuration**: Use Redis for caching in production
2. **Asset Optimization**: Compile and optimize assets before deployment
3. **Database Optimization**: Use database indexing and query optimization
4. **Queue Management**: Use queues for background job processing
5. **CDN Usage**: Use CDN for static assets in production

### Deployment Best Practices

1. **Maintenance Mode**: Use maintenance mode during deployment
2. **Database Backups**: Always backup database before migrations
3. **Health Checks**: Implement health checks after deployment
4. **Rollback Strategy**: Have a rollback strategy in place
5. **Monitoring**: Monitor application performance after deployment

## 🎯 Laravel Troubleshooting

### Common Laravel Issues

1. **Storage Permissions**: Ensure storage directory is writable
2. **Cache Issues**: Clear cache if experiencing issues
3. **Queue Problems**: Restart queue workers after deployment
4. **Database Connection**: Verify database credentials and connection
5. **Asset Compilation**: Ensure Node.js and build tools are available

### Debugging Laravel Deployments

```bash
# Check Laravel logs
vendor/bin/dep run "tail -f storage/logs/laravel.log"

# Check application status
vendor/bin/dep run "php artisan about"

# Check configuration
vendor/bin/dep run "php artisan config:show"

# Check routes
vendor/bin/dep run "php artisan route:list"
```

## 🎯 Next Steps

- **Read the [Configuration Reference](../configuration-reference.md)** - Complete configuration options
- **Explore [Examples](../examples/)** - Real-world Laravel deployment examples
- **Check [Best Practices](../best-practices.md)** - Laravel deployment best practices
- **Review [Task Reference](../task-reference.md)** - Available Laravel tasks
