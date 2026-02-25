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
    'supports_vite' => false,               // Enable Vite support
    'supports_mix' => false,                // Enable Mix support
    'supports_queue' => false,              // Enable queue support
    'supports_schedule' => false,           // Enable scheduler support
    'supports_horizon' => false,            // Enable Horizon support
    'supports_telescope' => false,          // Enable Telescope support
]);
```

### Advanced Laravel Configuration

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
    'supports_passport' => true,
    'supports_queue' => true,
    'supports_schedule' => true,
    'supports_horizon' => false,
    'supports_telescope' => false,
    'artisan_commands' => [
        'config:cache',
        'route:cache',
        'view:cache',
        'queue:restart',
    ],
    'maintenance_mode' => true,
    'maintenance_message' => 'Deploying...',
    'maintenance_retry' => 60,
]);
```

## 🎯 Laravel-Specific Tasks

### Available Laravel Tasks

#### `deploy:laravel`

Main Laravel deployment task that runs all Laravel-specific operations.

```bash
vendor/bin/dep deploy:laravel
```

#### `deploy:laravel:env`

Configure Laravel environment file.

```bash
vendor/bin/dep deploy:laravel:env
```

#### `deploy:laravel:storage`

Configure Laravel storage directories and symlinks.

```bash
vendor/bin/dep deploy:laravel:storage
```

#### `deploy:laravel:cache`

Clear and rebuild Laravel caches.

```bash
vendor/bin/dep deploy:laravel:cache
```

#### `deploy:laravel:migrate`

Run Laravel database migrations.

```bash
vendor/bin/dep deploy:laravel:migrate
```

#### `deploy:laravel:seed`

Run Laravel database seeders.

```bash
vendor/bin/dep deploy:laravel:seed
```

#### `deploy:laravel:passport`

Configure Laravel Passport OAuth.

```bash
vendor/bin/dep deploy:laravel:passport
```

#### `deploy:laravel:optimize`

Optimize Laravel application.

```bash
vendor/bin/dep deploy:laravel:optimize
```

## 🎯 Laravel Environment Configuration

### Environment File Setup

```php
// Configure environment files
klytron_configure_project([
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'env_backup_enabled' => true,
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
klytron_set_env('APP_ENV', 'production');
klytron_set_env('APP_DEBUG', 'false');
klytron_set_env('APP_URL', 'https://myapp.com');
klytron_set_env('LOG_CHANNEL', 'stack');
klytron_set_env('CACHE_DRIVER', 'redis');
klytron_set_env('SESSION_DRIVER', 'redis');
klytron_set_env('QUEUE_CONNECTION', 'redis');
```

## 🎯 Laravel Database Management

### Database Configuration

```php
// Configure database
klytron_configure_project([
    'database' => 'mysql',
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'myapp',
    'db_user' => 'root',
    'db_password' => 'secret',
    'db_charset' => 'utf8mb4',
    'db_collation' => 'utf8mb4_unicode_ci',
]);
```

### Database Migrations

```php
// Enable database migrations
klytron_configure_project([
    'database' => 'mysql',
    'migrations' => true,
    'backup_before_migrate' => true,
    'migration_table' => 'migrations',
]);
```

### Database Seeders

```php
// Enable database seeding
klytron_configure_project([
    'database' => 'mysql',
    'seeds' => true,
    'seeders' => ['UserSeeder', 'RoleSeeder'],
]);
```

## 🎯 Laravel Asset Management

### Vite Asset Compilation

The package provides intelligent Vite asset compilation with automatic Node.js detection and fallback to local builds when needed.

```php
// Configure Vite support
klytron_configure_project([
    'supports_vite' => true,
    'vite_build_command' => 'npm run build',
    'vite_dev_command' => 'npm run dev',
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
// Configure Mix support
klytron_configure_project([
    'supports_mix' => true,
    'mix_build_command' => 'npm run production',
    'mix_dev_command' => 'npm run dev',
]);
```

### Asset Optimization

```php
// Configure asset optimization
klytron_configure_project([
    'supports_vite' => true,
    'asset_optimization' => true,
    'asset_compression' => true,
    'cdn_enabled' => true,
    'asset_url' => 'https://cdn.myapp.com',
]);
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
// Configure cache settings
klytron_configure_project([
    'cache_driver' => 'redis',
    'session_driver' => 'redis',
    'queue_connection' => 'redis',
    'cache_clear_on_deploy' => true,
]);
```

### Cache Optimization

```php
// Configure cache optimization
klytron_configure_project([
    'cache_optimization' => true,
    'config_cache' => true,
    'route_cache' => true,
    'view_cache' => true,
]);
```

## 🎯 Laravel Queue Management

### Queue Configuration

```php
// Configure queue support
klytron_configure_project([
    'supports_queue' => true,
    'queue_connection' => 'redis',
    'queue_restart_on_deploy' => true,
    'queue_workers' => 2,
]);
```

### Queue Workers

```php
// Configure queue workers
klytron_add_task('deploy:laravel:queue', function () {
    run('php artisan queue:restart');
    run('php artisan queue:work --daemon --sleep=3 --tries=3');
}, [
    'description' => 'Restart Laravel queue workers',
]);
```

## 🎯 Laravel Passport Configuration

### Passport Setup

```php
// Configure Passport support
klytron_configure_project([
    'supports_passport' => true,
    'passport_keys_path' => 'storage/oauth-*.key',
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
// Add Passport installation task
klytron_add_task('deploy:laravel:passport_install', function () {
    run('php artisan passport:install');
}, [
    'description' => 'Install Laravel Passport',
]);
```

## 🎯 Laravel Maintenance Mode

### Maintenance Mode Configuration

```php
// Configure maintenance mode
klytron_configure_project([
    'maintenance_mode' => true,
    'maintenance_message' => 'Deploying...',
    'maintenance_retry' => 60,
    'maintenance_secret' => 'secret-token',
]);
```

### Maintenance Mode Tasks

```php
// Add maintenance mode tasks
klytron_add_task('deploy:laravel:maintenance_on', function () {
    $message = get('maintenance_message', 'Deploying...');
    $retry = get('maintenance_retry', 60);
    run("php artisan down --message='{$message}' --retry={$retry}");
}, [
    'description' => 'Enable Laravel maintenance mode',
]);

klytron_add_task('deploy:laravel:maintenance_off', function () {
    run('php artisan up');
}, [
    'description' => 'Disable Laravel maintenance mode',
]);
```

## 🎯 Laravel Optimization

### Application Optimization

```php
// Configure Laravel optimization
klytron_configure_project([
    'laravel_optimization' => true,
    'optimize_autoloader' => true,
    'optimize_config' => true,
    'optimize_routes' => true,
    'optimize_views' => true,
]);
```

### Performance Optimization

```php
// Add optimization tasks
klytron_add_task('deploy:laravel:optimize', function () {
    run('php artisan config:cache');
    run('php artisan route:cache');
    run('php artisan view:cache');
    run('composer dump-autoload --optimize');
}, [
    'description' => 'Optimize Laravel application',
]);
```

## 🎯 Laravel Health Checks

### Application Health Checks

```php
// Add Laravel health checks
klytron_add_task('deploy:laravel:health_check', function () {
    $healthChecks = [
        'Application accessible' => 'curl -f http://localhost/health || exit 1',
        'Database connection' => 'php artisan db:monitor',
        'Cache working' => 'php artisan cache:test',
        'Queue working' => 'php artisan queue:monitor',
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
}, [
    'description' => 'Run Laravel health checks',
]);
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
    'db_host' => 'localhost',
    'db_name' => 'myapp',
    'db_user' => 'root',
    'db_password' => 'secret',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'supports_vite' => true,
    'supports_storage_link' => true,
    'supports_passport' => true,
    'supports_queue' => true,
    'supports_schedule' => true,
    'maintenance_mode' => true,
    'cache_driver' => 'redis',
    'session_driver' => 'redis',
    'queue_connection' => 'redis',
]);

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
    'type' => 'laravel-api',
    'database' => 'postgresql',
    'db_host' => 'localhost',
    'db_name' => 'myapp_api',
    'db_user' => 'postgres',
    'db_password' => 'secret',
    'supports_passport' => true,
    'supports_rate_limiting' => true,
    'supports_api_docs' => true,
    'cache_driver' => 'redis',
    'session_driver' => 'redis',
]);

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
