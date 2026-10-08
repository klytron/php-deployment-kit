# 🚀 Yii2 Deployment Guide

Complete guide for deploying Yii2 applications with Klytron Deployer. This guide covers Yii2 Advanced Application Template, multi-application structure, and Yii2-specific deployment features.

## 🎯 Yii2 Features

Klytron Deployer provides comprehensive support for Yii2 applications:

- **Advanced Template** - Full support for Yii2 Advanced Application Template
- **Multi-Application** - Deploy frontend, backend, and API applications
- **Database Migrations** - Safe Yii2 database migration handling
- **Asset Management** - Yii2 asset compilation and optimization
- **Cache Management** - Yii2 cache configuration and optimization
- **Maintenance Mode** - Yii2 maintenance mode support
- **Console Commands** - Yii2 console command execution
- **Environment Configuration** - Yii2 environment-specific configuration
- **Security** - Yii2 security features and configuration

## 🎯 Quick Start for Yii2

### 1. Install Klytron Deployer

```bash
composer require klytron/php-deployment-kit
```

### 2. Copy Yii2 Template

```bash
cp vendor/klytron/php-deployment-kit/templates/yii2-deploy.php.template deploy.php
```

### 3. Configure Your Yii2 Application

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-yii2-recipe.php';

// Configure Yii2 application
klytron_configure_app('my-yii2-app', 'git@github.com:user/my-yii2-app.git');

// Set deployment paths
klytron_set_paths('/var/www', '/var/www/html');

// Configure Yii2 project (real kit keys)
klytron_configure_project([
    'type' => 'yii2',
    'database' => 'mysql',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
]);

// Configure host
klytron_configure_host('myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
    'http_group' => 'www-data',
]);
```

### 4. Deploy

```bash
vendor/bin/dep deploy
```

## 🎯 Yii2 Configuration Options

### Basic Yii2 Configuration

```php
klytron_configure_project([
    'type' => 'yii2',                       // Yii2 project type
    'database' => 'mysql',                  // Database type
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
]);
// Database credentials are plain Deployer config (note `db_pass`), not
// project flags:
set('db_host', 'localhost');
set('db_name', 'myyii2app');
set('db_user', 'root');
set('db_pass', 'secret');
```

### Advanced Yii2 Configuration

```php
klytron_configure_yii2_app('my-yii2-app', 'git@github.com:user/my-yii2-app.git', [
    'database_type' => 'mysql',
    'env_file' => '.env.production',
    'public_dir_path' => 'frontend/web',
    'shared_dir_path' => 'shared',
]);
// Database credentials are plain Deployer config (note `db_pass`), not
// helper options:
set('db_host', 'localhost');
set('db_name', 'myyii2app');
set('db_user', 'root');
set('db_pass', 'secret');
```

## 🎯 Yii2-Specific Tasks

### Available Yii2 Tasks

#### `klytron:yii2:migrate`

Run Yii2 database migrations.

```bash
vendor/bin/dep klytron:yii2:migrate
```

#### `klytron:yii2:deploy:configure:interactive`

Interactive Yii2 deployment configuration (validates env, domain, settings).

```bash
vendor/bin/dep klytron:yii2:deploy:configure:interactive
```

#### `klytron:yii2:compile_assets`

Compile Yii2 assets.

```bash
vendor/bin/dep klytron:yii2:compile_assets
```

#### `klytron:yii2:clear_cache` / `klytron:yii2:warmup_cache`

Clear the Yii2 cache, then warm it up.

```bash
vendor/bin/dep klytron:yii2:clear_cache
vendor/bin/dep klytron:yii2:warmup_cache
```

#### `klytron:yii2:maintenance_enable` / `klytron:yii2:maintenance_disable`

Enable or disable Yii2 maintenance mode.

```bash
vendor/bin/dep klytron:yii2:maintenance_enable
vendor/bin/dep klytron:yii2:maintenance_disable
```

#### `klytron:yii2:copy_console`

Copy the Yii2 console entry point.

```bash
vendor/bin/dep klytron:yii2:copy_console
```

## 🎯 Yii2 Application Structure

### Advanced Application Template

```php
// Configure Yii2 Advanced Application Template (paths consumed by the
// recipe via public_dir_path / shared_dir_path)
klytron_configure_yii2_app('my-yii2-app', 'git@github.com:user/my-yii2-app.git', [
    'database_type' => 'mysql',
    'public_dir_path' => 'frontend/web',
    'shared_dir_path' => 'shared',
]);
```

### Basic Application Template

```php
// Configure Yii2 Basic Application Template
klytron_configure_yii2_app('my-yii2-app', 'git@github.com:user/my-yii2-app.git', [
    'database_type' => 'mysql',
    'public_dir_path' => 'web',
]);
```

## 🎯 Yii2 Environment Configuration

### Environment Files

```php
// Configure Yii2 environment files (real kit keys)
klytron_configure_project([
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
]);
// Yii2 runtime env (YII_ENV / YII_DEBUG) is application config — set it in
// your .env file or entry scripts, not in kit config.
```

### Environment Variables

```php
// Set Yii2 environment variables
set('YII_ENV', 'prod');
set('YII_DEBUG', 'false');
set('YII_ENABLE_ERROR_HANDLER', 'false');
set('YII_ENABLE_EXCEPTION_HANDLER', 'false');
```

## 🎯 Yii2 Database Management

### Database Configuration

```php
// Configure Yii2 database (real signature: string $type first)
klytron_configure_database('mysql', [
    'import_path' => 'database/live-db-exports',
    'supports_migrations' => true,
    'supports_seeders' => true,
]);
// Credentials for dumps are plain Deployer config (note `db_pass`):
set('db_host', 'localhost');
set('db_port', 3306);
set('db_name', 'myyii2app');
set('db_user', 'root');
set('db_pass', 'secret');
```

### Database Migrations

```php
// Migrations run via the kit task klytron:yii2:migrate. Migration paths and
// namespaces are Yii2 application config (console/config/main.php), not kit
// config — no kit flags are needed.
```

### Migration Tasks

```php
// Add Yii2 migration tasks
task('deploy:yii2:migrate', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Running migrations for {$app}...</info>");
        run("php yii migrate/up --interactive=0 --app={$app}");
    }
})->desc('Run Yii2 database migrations');

task('deploy:yii2:migrate:down', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Rolling back migrations for {$app}...</info>");
        run("php yii migrate/down 1 --interactive=0 --app={$app}");
    }
})->desc('Rollback Yii2 database migrations');
```

## 🎯 Yii2 Asset Management

### Asset Configuration

```php
// Asset compilation runs via the kit task klytron:yii2:compile_assets.
// No kit flags are needed — wire that task into your deploy flow.
```

### Asset Tasks

```php
// Add Yii2 asset tasks
task('deploy:yii2:assets', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Compiling assets for {$app}...</info>");
        
        // Compile assets
        run("php yii asset/compress --interactive=0 --app={$app}");
        
        // Publish assets
        run("php yii asset/publish --interactive=0 --app={$app}");
    }
})->desc('Compile Yii2 assets');

task('deploy:yii2:assets:clear', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Clearing assets for {$app}...</info>");
        run("php yii asset/clear --interactive=0 --app={$app}");
    }
})->desc('Clear Yii2 assets');
```

## 🎯 Yii2 Cache Management

### Cache Configuration

```php
// Cache handling runs via the kit tasks klytron:yii2:clear_cache and
// klytron:yii2:warmup_cache. No kit flags are needed — wire those tasks
// into your deploy flow.
```

### Cache Tasks

```php
// Add Yii2 cache tasks
task('deploy:yii2:cache', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Configuring cache for {$app}...</info>");
        
        // Clear cache
        run("php yii cache/flush-all --interactive=0 --app={$app}");
        
        // Warm cache
        if (get('cache_warming', false)) {
            run("php yii cache/warm --interactive=0 --app={$app}");
        }
    }
})->desc('Configure Yii2 cache');

task('deploy:yii2:cache:clear', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Clearing cache for {$app}...</info>");
        run("php yii cache/flush-all --interactive=0 --app={$app}");
    }
})->desc('Clear Yii2 cache');
```

## 🎯 Yii2 Maintenance Mode

### Maintenance Configuration

```php
// Maintenance mode runs via the kit tasks klytron:yii2:maintenance_enable
// and klytron:yii2:maintenance_disable. The custom tasks below read their
// own values via get() with defaults — set them with set() if you use them:
set('maintenance_message', 'Site is under maintenance');
set('maintenance_retry', 60);
```

### Maintenance Tasks

```php
// Add Yii2 maintenance tasks
task('deploy:yii2:maintenance:on', function () {
    $apps = get('yii2_apps', ['frontend']);
    $message = get('maintenance_message', 'Site is under maintenance');
    $retry = get('maintenance_retry', 60);
    
    foreach ($apps as $app) {
        writeln("<info>Enabling maintenance mode for {$app}...</info>");
        run("php yii maintenance/enable --message='{$message}' --retry={$retry} --app={$app}");
    }
})->desc('Enable Yii2 maintenance mode');

task('deploy:yii2:maintenance:off', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Disabling maintenance mode for {$app}...</info>");
        run("php yii maintenance/disable --app={$app}");
    }
})->desc('Disable Yii2 maintenance mode');
```

## 🎯 Yii2 Console Commands

### Console Configuration

```php
// Command lists for the custom console task below are your own values —
// store them with set() (the task reads them via get() with defaults):
set('console_commands', [
    'migrate/up',
    'cache/flush-all',
    'rbac/init',
    'user/create',
]);
set('console_commands_per_app', [
    'frontend' => ['migrate/up', 'cache/flush-all'],
    'backend' => ['rbac/init', 'user/create'],
]);
```

### Console Tasks

```php
// Add Yii2 console tasks
task('deploy:yii2:console', function () {
    $commands = get('console_commands', []);
    $commandsPerApp = get('console_commands_per_app', []);
    
    // Run global commands
    foreach ($commands as $command) {
        writeln("<info>Running console command: {$command}</info>");
        run("php yii {$command} --interactive=0");
    }
    
    // Run app-specific commands
    foreach ($commandsPerApp as $app => $appCommands) {
        foreach ($appCommands as $command) {
            writeln("<info>Running console command for {$app}: {$command}</info>");
            run("php yii {$command} --interactive=0 --app={$app}");
        }
    }
})->desc('Run Yii2 console commands');
```

## 🎯 Yii2 Security

### Security Configuration

```php
// Yii2 security hardening is application config (request/params components),
// not kit config — no kit flags are needed. The custom task below is a
// per-project example.
```

### Security Tasks

```php
// Add Yii2 security tasks
task('deploy:yii2:security', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Configuring security for {$app}...</info>");
        
        // Configure security settings
        run("php yii security/configure --app={$app}");
        
        // Generate security keys
        run("php yii security/generate-keys --app={$app}");
    }
})->desc('Configure Yii2 security');
```

## 🎯 Yii2 Health Checks

### Health Check Configuration

```php
// Endpoint lists for the custom health-check task below are your own values —
// store them with set() (the task reads them via get() with defaults):
set('health_check_endpoints', [
    '/site/health',
    '/api/health',
    '/admin/health',
]);
set('health_check_timeout', 30);
```

### Health Check Tasks

```php
// Add Yii2 health check tasks
task('deploy:yii2:health_check', function () {
    $apps = get('yii2_apps', ['frontend']);
    $endpoints = get('health_check_endpoints', ['/site/health']);
    $timeout = get('health_check_timeout', 30);
    
    foreach ($apps as $app) {
        foreach ($endpoints as $endpoint) {
            try {
                writeln("<info>Checking health for {$app}: {$endpoint}</info>");
                run("curl -f --max-time {$timeout} http://localhost{$endpoint} || exit 1");
                writeln("<info>✓ {$app} {$endpoint} is healthy</info>");
            } catch (Exception $e) {
                writeln("<error>✗ {$app} {$endpoint} health check failed</error>");
                throw $e;
            }
        }
    }
})->desc('Run Yii2 health checks');
```

## 🎯 Yii2 Deployment Examples

### Basic Yii2 Deployment

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-yii2-recipe.php';

// Basic Yii2 configuration
klytron_configure_app('my-yii2-app', 'git@github.com:user/my-yii2-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('myapp.com');

klytron_configure_project([
    'type' => 'yii2',
    'database' => 'mysql',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
]);

klytron_configure_host('myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);
```

### Advanced Yii2 Deployment

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-yii2-recipe.php';

// Advanced Yii2 configuration
klytron_configure_app('my-advanced-yii2-app', 'git@github.com:user/my-yii2-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('myapp.com');

klytron_configure_project([
    'type' => 'yii2',
    'database' => 'mysql',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
]);
// Database credentials are plain Deployer config (note `db_pass`), not
// project flags:
set('db_host', 'localhost');
set('db_name', 'myyii2app');
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
    'common/config/main-local.php',
    'common/config/params-local.php',
    'frontend/config/main-local.php',
    'frontend/config/params-local.php',
    'backend/config/main-local.php',
    'backend/config/params-local.php',
]);

klytron_configure_shared_dirs([
    'common/runtime',
    'frontend/runtime',
    'backend/runtime',
    'frontend/web/assets',
    'backend/web/assets',
    'console/runtime',
]);

klytron_configure_writable_dirs([
    'common/runtime',
    'frontend/runtime',
    'backend/runtime',
    'frontend/web/assets',
    'backend/web/assets',
    'console/runtime',
]);
```

### Yii2 Basic Template Deployment

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-yii2-recipe.php';

// Yii2 Basic Template configuration
klytron_configure_app('my-yii2-basic', 'git@github.com:user/my-yii2-basic.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('basic.myapp.com');

klytron_configure_project([
    'type' => 'yii2',
    'yii2_app_type' => 'basic',
    'yii2_apps' => ['web'],
    'database' => 'mysql',
    'supports_maintenance' => true,
    'supports_assets' => true,
]);

klytron_configure_host('basic.myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);
```

## 🎯 Yii2 Best Practices

### Application Structure Best Practices

1. **Use Advanced Template**: Use Yii2 Advanced Application Template for complex projects
2. **Separate Applications**: Keep frontend, backend, and API separate
3. **Common Code**: Put shared code in the `common` directory
4. **Configuration**: Use environment-specific configuration files
5. **Assets**: Organize assets properly for each application

### Performance Best Practices

1. **Asset Optimization**: Enable asset compression and optimization
2. **Cache Configuration**: Use Redis or Memcached for caching
3. **Database Optimization**: Use database indexing and query optimization
4. **CDN Usage**: Use CDN for static assets
5. **Gzip Compression**: Enable gzip compression for better performance

### Security Best Practices

1. **Environment Configuration**: Use proper environment configuration
2. **Security Headers**: Implement security headers
3. **CSRF Protection**: Enable CSRF validation
4. **Input Validation**: Validate all user inputs
5. **Error Handling**: Don't expose sensitive information in errors

### Deployment Best Practices

1. **Maintenance Mode**: Use maintenance mode during deployment
2. **Database Migrations**: Always backup before running migrations
3. **Asset Compilation**: Compile assets before deployment
4. **Cache Management**: Clear and warm cache after deployment
5. **Health Checks**: Implement health checks after deployment

## 🎯 Yii2 Troubleshooting

### Common Yii2 Issues

1. **Migration Errors**: Check database connection and migration files
2. **Asset Issues**: Verify asset compilation and permissions
3. **Cache Problems**: Check cache configuration and permissions
4. **Maintenance Mode**: Ensure maintenance mode is properly configured
5. **Console Commands**: Verify console command syntax and permissions

### Debugging Yii2 Deployments

```bash
# Check Yii2 application status
vendor/bin/dep run "php yii about"

# Check database connection
vendor/bin/dep run "php yii db/check"

# Check migrations
vendor/bin/dep run "php yii migrate/history"

# Check cache status
vendor/bin/dep run "php yii cache/info"

# Check asset status
vendor/bin/dep run "php yii asset/info"

# Check maintenance mode
vendor/bin/dep run "php yii maintenance/status"

# Check application logs
vendor/bin/dep run "tail -f common/runtime/logs/app.log"
vendor/bin/dep run "tail -f frontend/runtime/logs/app.log"
vendor/bin/dep run "tail -f backend/runtime/logs/app.log"
```

## 🎯 Next Steps

- **Read the [Configuration Reference](../configuration-reference.md)** - Complete configuration options
- **Explore [Examples](../examples/)** - Real-world Yii2 deployment examples
- **Check [Best Practices](../best-practices.md)** - Yii2 deployment best practices
- **Review [Task Reference](../task-reference.md)** - Available Yii2 tasks
