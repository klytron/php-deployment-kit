# Quick Start Guide

Get up and running with PHP Deployment Kit in under 5 minutes.

## Prerequisites

- **PHP 8.1+** on your local machine
- **Composer** installed
- A **Git** repository for your project
- **SSH access** to your deployment server

## Step 1: Install

```bash
composer require klytron/php-deployment-kit --dev
```

Verify:

```bash
vendor/bin/dep --version
# Deployer 7.x.x
```

## Step 2: Create deploy.php

Create a `deploy.php` in your project root. The file is a **per-project customiser** — all deployment logic lives in the package.

### Laravel project

```php
<?php
namespace Deployer;

// Core kit + Laravel-specific tasks
require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';
require __DIR__ . '/vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Application — deploy_path = {parent}/{app-name}
klytron_configure_app('my-app', 'git@github.com:my-org/my-app.git', [
    'keep_releases'   => 3,
    'default_timeout' => 1800,
]);

// Where does the app live on the server?
klytron_set_paths('/var/www', '/var/www/${APP_URL_DOMAIN}/public_html');
klytron_set_domain('my-app.com');    // Resolves ${APP_URL_DOMAIN}
klytron_set_php_version('php8.3');

// What does this project use?
klytron_configure_project([
    'type'                  => 'laravel',
    'database'              => 'mysql',         // mysql|mariadb|postgresql|sqlite|none
    'env_file_local'        => '.env.production',
    'env_file_remote'       => '.env',
    'supports_vite'         => true,            // npm run build (Vite)
    'supports_storage_link' => true,            // artisan storage:link
]);

// Your server
klytron_configure_host('your-server.com', [
    'remote_user' => 'deploy',
    'branch'      => 'main',
    'http_user'   => 'www-data',
    'http_group'  => 'www-data',
    'labels'      => ['stage' => 'production'],
]);

// Shared across releases
klytron_configure_shared_files(['.env']);
klytron_configure_shared_dirs(['storage', 'public/storage', 'bootstrap/cache']);
klytron_configure_writable_dirs([
    'bootstrap/cache', 'storage', 'storage/app', 'storage/app/public',
    'storage/framework', 'storage/framework/cache', 'storage/framework/sessions',
    'storage/framework/views', 'storage/logs', 'public/storage',
]);

// Full Laravel pipeline
task('deploy', [
    'klytron:deploy:start_timer',
    'deploy:unlock',
    'klytron:deploy:fix_repo',
    'klytron:laravel:deploy:prepare:complete',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'klytron:deploy:fix_git_ownership',
    'klytron:laravel:deploy:environment:complete',
    'deploy:vendors',
    'klytron:laravel:node:vite:build',
    'klytron:laravel:deploy:database:complete',
    'deploy:writable',
    'klytron:laravel:deploy:cache:complete',
    'deploy:symlink',
    'klytron:laravel:deploy:finalize:complete',
    'klytron:assets:map',
    'klytron:assets:cleanup',
    'klytron:fonts:verify',
    'deploy:unlock',
    'deploy:cleanup',
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

### Simple PHP project

```php
<?php
namespace Deployer;

// Core kit + plain PHP recipe
require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';
require __DIR__ . '/vendor/klytron/php-deployment-kit/recipes/klytron-php-recipe.php';

klytron_configure_app('my-site', 'git@github.com:my-org/my-site.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_php_version('php8.3');

klytron_configure_project(['type' => 'php', 'database' => 'none']);

klytron_configure_host('your-server.com', [
    'remote_user' => 'deploy',
    'branch'      => 'main',
    'http_user'   => 'www-data',
    'http_group'  => 'www-data',
]);

klytron_configure_shared_files(['.env']);
klytron_configure_shared_dirs(['uploads', 'cache', 'logs']);
klytron_configure_writable_dirs(['uploads', 'cache', 'logs']);

task('deploy', [
    'klytron:deploy:start_timer',
    'deploy:unlock',
    'klytron:deploy:fix_repo',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'klytron:upload:env:production',
    'deploy:vendors',
    'deploy:writable',
    'deploy:symlink',
    'klytron:deploy:access_permissions',
    'deploy:unlock',
    'deploy:cleanup',
    'klytron:deploy:end_timer',
])->desc('Deploy to production');
```

> **Template files** — copy a template instead of writing from scratch:
> - `vendor/klytron/php-deployment-kit/templates/laravel-deploy.php.template`
> - `vendor/klytron/php-deployment-kit/templates/simple-php.php.template`
> - `vendor/klytron/php-deployment-kit/templates/deploy.php.template` (generic)

## Step 3: Deploy

```bash
# Run the deployment
vendor/bin/dep deploy

# Dry-run (plan without executing)
vendor/bin/dep deploy --dry-run

# Verbose output
vendor/bin/dep deploy -v
```

## Common project configurations

### Laravel with Vite (no database)

```php
klytron_configure_project([
    'type'                  => 'laravel',
    'database'              => 'none',
    'env_file_local'        => '.env.production',
    'env_file_remote'       => '.env',
    'supports_nodejs'       => true,
    'supports_vite'         => true,
    'supports_storage_link' => true,
    'verify_fonts'          => true,
]);
```

### Laravel API with PostgreSQL + Passport

```php
klytron_configure_project([
    'type'              => 'laravel',
    'database'          => 'postgresql',
    'env_file_local'    => '.env.production',
    'env_file_remote'   => '.env',
    'supports_passport' => true,
    'supports_vite'     => false,
]);
```

### Laravel with encrypted .env

```php
klytron_configure_project([
    'type'              => 'laravel',
    'database'          => 'mysql',
    'env_file_local'    => '.env.production',
    'env_file_remote'   => '.env',
    'enable_encryption' => true,  // Reads LARAVEL_ENV_ENCRYPTION_KEY env var
]);
```

To use encryption, export your key before deploying:

```bash
export LARAVEL_ENV_ENCRYPTION_KEY="$(gopass show -o Apps/laravel/laravel-env-encryption-key)"
vendor/bin/dep deploy
```

## Unattended / CI deployments

Add these to `deploy.php` to skip interactive prompts:

```php
set('auto_confirm_production', true);
set('auto_deployment_type', 'update');        // 'update' | 'fresh'
set('auto_upload_env', true);
set('auto_database_operation', 'migrations'); // 'migrations'|'import'|'both'|'none'
set('auto_clear_caches', true);
set('auto_confirm_settings', true);
```

## Server config files

Copy environment-specific files from your repo into each release automatically:

```php
set('server_config_files', [
    [
        'source'    => 'server/.htaccess.production',
        'target'    => 'public/.htaccess',
        'mode'      => 0644,
        'overwrite' => true,
    ],
]);

after('deploy:shared', 'klytron:server:deploy:configs');
```

## Troubleshooting

| Problem | Solution |
|---|---|
| SSH connection failed | Test with `ssh user@server.com` first |
| Permission denied | Check `http_user`/`http_group` match your web server |
| deploy.lock exists | Run `vendor/bin/dep deploy:unlock` |
| Git safe-directory error | The `klytron:deploy:fix_repo` task handles this automatically |

- [Configuration Reference](configuration-reference.md) — every option explained
- [Task Reference](task-reference.md) — every available task
- [Troubleshooting Guide](troubleshooting.md)
