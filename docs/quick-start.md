# Quick Start Guide

Get up and running with PHP Deployment Kit in under 5 minutes!

## Prerequisites

Before you begin, ensure you have:

- **PHP 8.1+** installed on your local machine
- **Composer** installed and configured
- **Git** repository for your project
- **SSH access** to your deployment server
- **Server with PHP 8.1+** and required extensions

## Step 1: Install

```bash
cd /path/to/your/project
composer require klytron/php-deployment-kit --dev
```

Verify installation:

```bash
vendor/bin/dep --version
# Output: Deployer 7.x.x
```

## Step 2: Create deploy.php

Create a `deploy.php` file in your project root:

### For Laravel Projects

```php
<?php
namespace Deployer;

// Include the PHP Deployment Kit (framework-agnostic core)
require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';

// Include the Laravel Recipe for Laravel-specific tasks
require __DIR__ . '/vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Configure your application
klytron_configure_app(
    'my-awesome-app',
    'git@github.com:username/my-app.git',
    [
        'keep_releases' => 3,
        'default_timeout' => 1800,
    ]
);

// Set deployment paths
klytron_set_paths(
    '/var/www',
    '/var/www/html'
);

// Set domain
klytron_set_domain('your-domain.com');

// Set PHP version
klytron_set_php_version('php8.3');

// Configure project capabilities
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'supports_vite' => true,
    'supports_storage_link' => true,
]);

// Configure your server
klytron_configure_host('your-domain.com', [
    'remote_user' => 'root',
    'branch' => 'main',
    'http_user' => 'www-data',
    'http_group' => 'www-data',
]);

// Configure shared files/directories
klytron_configure_shared_files([
    '.env',
]);

klytron_configure_shared_dirs([
    'storage',
    'public/storage',
    'bootstrap/cache',
]);

klytron_configure_writable_dirs([
    'bootstrap/cache',
    'storage',
    'storage/app/public',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'public/storage',
]);

// Define deployment flow
task('deploy', [
    'klytron:deploy:start_timer',
    'klytron:laravel:deploy:display:info',
    'klytron:laravel:deploy:configure:interactive',
    'deploy:unlock',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:env',
    'deploy:vendors',
    'deploy:writable',
    'deploy:symlink',
    'deploy:cleanup',
    'klytron:deploy:end_timer',
])->desc('Deploy my application');
```

### For Simple PHP Projects

```php
<?php
namespace Deployer;

// Include only the framework-agnostic core
require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';

klytron_configure_app('my-php-app', 'git@github.com:user/my-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('myapp.com');

klytron_configure_project([
    'type' => 'simple-php',
    'database' => 'none',
]);

klytron_configure_host('myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);

task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:symlink',
    'deploy:cleanup',
])->desc('Deploy my PHP application');
```

## Step 3: Test Configuration

```bash
# Test the configuration
vendor/bin/dep test

# List available tasks
vendor/bin/dep list

# Check deployment dry-run
vendor/bin/dep deploy --dry-run
```

## Step 4: Deploy

```bash
# Deploy your application
vendor/bin/dep deploy

# Deploy to specific host
vendor/bin/dep deploy your-domain.com

# Deploy with verbose output
vendor/bin/dep deploy -v
```

## Step 5: Verify Deployment

```bash
# Check current release
vendor/bin/dep current

# List all releases
vendor/bin/dep releases
```

## Common Configuration Examples

### Laravel with MariaDB

```php
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mariadb',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'supports_vite' => true,
    'supports_storage_link' => true,
    'supports_sitemap' => true,
]);
```

### Laravel API with PostgreSQL

```php
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'postgresql',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'supports_passport' => true,
    'supports_vite' => true,
]);
```

## Laravel Environment File Encryption

The PHP Deployment Kit supports Laravel's built-in environment file encryption for secure secret management.

### How It Works

1. **Local Development**: You edit normal `.env` and `.env.production` files
2. **Encryption**: Files are encrypted to `.env.encrypted` and `.env.production.encrypted`
3. **Git Storage**: Only encrypted files are committed (plaintext is gitignored)
4. **Deployment**: Automatic decryption before upload using `LARAVEL_ENV_ENCRYPTION_KEY`

### Setup

#### 1. Configure Encryption in deploy.php

```php
// Enable environment file encryption
set('env_encryption_environments', ['production', 'local']);
set('env_encryption_force', true);

// Configure which files to use
klytron_configure_project([
    'type' => 'laravel',
    'env_file_local' => '.env.production',   // Local file to upload
    'env_file_remote' => '.env',               // Remote filename
    // ... other options
]);
```

#### 2. Store Encryption Key in Gopass

```bash
# Generate or retrieve your Laravel env encryption key
# Store it in gopass for secure access
gopass insert Apps/laravel/laravel-env-encryption-key
```

#### 3. Encrypt Your Environment Files

```bash
# Export the key for the current session
export LARAVEL_ENV_ENCRYPTION_KEY="$(gopass show -o Apps/laravel/laravel-env-encryption-key)"

# Encrypt local .env
ddev php artisan env:encrypt --readable

# Encrypt production .env
ddev php artisan env:encrypt --env=production --readable

# Commit only encrypted files
git add -A
git commit -m "chore: update encrypted environment files"
git push
```

### Deployment Workflow

```bash
# 1. Export the encryption key
export LARAVEL_ENV_ENCRYPTION_KEY="$(gopass show -o Apps/laravel/laravel-env-encryption-key)"

# 2. Run deployment (automatic decryption happens before upload)
ddev exec vendor/bin/dep deploy
```

The deployment kit automatically:
1. Checks if `.env.production` exists locally
2. If missing, decrypts from `.env.production.encrypted`
3. Uploads the decrypted file to the server
4. Server-side decryption happens if encrypted file exists on server

### Manual Decryption (if needed)

```bash
# Decrypt production environment
export LARAVEL_ENV_ENCRYPTION_KEY="$(gopass show -o Apps/laravel/laravel-env-encryption-key)"
ddev php artisan env:decrypt --env=production --force

# Or use the deployment task
vendor/bin/dep klytron:laravel:local:env:ensure_decrypted
```

### Security Best Practices

- ✅ Never commit `.env` or `.env.production` (only `.env.encrypted`)
- ✅ Store `LARAVEL_ENV_ENCRYPTION_KEY` in gopass, never in code
- ✅ Export key only for the current shell session
- ✅ Use `--readable` flag when encrypting for version control diffs
- ✅ Rotate encryption keys periodically

---

## Troubleshooting

### SSH Connection Failed

```bash
# Test SSH connection
ssh user@your-domain.com

# Check SSH key
ls -la ~/.ssh/
```

### Permission Denied

```bash
# Check server permissions
vendor/bin/dep test

# Fix permissions on server
sudo chown -R www-data:www-data /var/www/html
```

### Need Help?

- [Documentation](docs/)
- [Configuration Reference](docs/configuration-reference.md)
- [GitHub Issues](https://github.com/klytron/php-deployment-kit/issues)
