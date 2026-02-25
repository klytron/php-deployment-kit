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

// Configure Yii2 project
klytron_configure_project([
    'type' => 'yii2',
    'yii2_app_type' => 'advanced',
    'yii2_apps' => ['frontend', 'backend', 'api'],
    'database' => 'mysql',
    'supports_maintenance' => true,
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
    'yii2_app_type' => 'advanced',          // Yii2 app type: basic, advanced
    'yii2_apps' => ['frontend', 'backend'], // Yii2 applications
    'database' => 'mysql',                  // Database type
    'supports_maintenance' => true,         // Enable maintenance mode
    'supports_assets' => true,              // Enable asset compilation
    'supports_cache' => true,               // Enable cache management
    'supports_console' => true,             // Enable console commands
]);
```

### Advanced Yii2 Configuration

```php
klytron_configure_project([
    'type' => 'yii2',
    'yii2_app_type' => 'advanced',
    'yii2_apps' => ['frontend', 'backend', 'api'],
    'database' => 'mysql',
    'db_host' => 'localhost',
    'db_name' => 'myyii2app',
    'db_user' => 'root',
    'db_password' => 'secret',
    'supports_maintenance' => true,
    'supports_assets' => true,
    'supports_cache' => true,
    'supports_console' => true,
    'maintenance_mode' => true,
    'maintenance_message' => 'Site is under maintenance',
    'maintenance_retry' => 60,
    'asset_compression' => true,
    'cache_optimization' => true,
    'console_commands' => [
        'migrate/up',
        'cache/flush-all',
        'rbac/init',
    ],
]);
```

## 🎯 Yii2-Specific Tasks

### Available Yii2 Tasks

#### `deploy:yii2`

Main Yii2 deployment task that runs all Yii2-specific operations.

```bash
vendor/bin/dep deploy:yii2
```

#### `deploy:yii2:init`

Initialize Yii2 application.

```bash
vendor/bin/dep deploy:yii2:init
```

#### `deploy:yii2:migrate`

Run Yii2 database migrations.

```bash
vendor/bin/dep deploy:yii2:migrate
```

#### `deploy:yii2:assets`

Compile Yii2 assets.

```bash
vendor/bin/dep deploy:yii2:assets
```

#### `deploy:yii2:cache`

Configure Yii2 cache.

```bash
vendor/bin/dep deploy:yii2:cache
```

#### `deploy:yii2:maintenance`

Configure Yii2 maintenance mode.

```bash
vendor/bin/dep deploy:yii2:maintenance
```

#### `deploy:yii2:console`

Run Yii2 console commands.

```bash
vendor/bin/dep deploy:yii2:console
```

## 🎯 Yii2 Application Structure

### Advanced Application Template

```php
// Configure Yii2 Advanced Application Template
klytron_configure_project([
    'type' => 'yii2',
    'yii2_app_type' => 'advanced',
    'yii2_apps' => ['frontend', 'backend', 'api'],
    'yii2_common_path' => 'common',
    'yii2_console_path' => 'console',
    'yii2_web_path' => 'web',
    'yii2_runtime_path' => 'runtime',
    'yii2_vendor_path' => 'vendor',
]);
```

### Basic Application Template

```php
// Configure Yii2 Basic Application Template
klytron_configure_project([
    'type' => 'yii2',
    'yii2_app_type' => 'basic',
    'yii2_apps' => ['web'],
    'yii2_web_path' => 'web',
    'yii2_runtime_path' => 'runtime',
    'yii2_vendor_path' => 'vendor',
]);
```

## 🎯 Yii2 Environment Configuration

### Environment Files

```php
// Configure Yii2 environment files
klytron_configure_project([
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'yii2_env' => 'prod',
    'yii2_debug' => false,
    'yii2_gii' => false,
]);
```

### Environment Variables

```php
// Set Yii2 environment variables
klytron_set_env('YII_ENV', 'prod');
klytron_set_env('YII_DEBUG', 'false');
klytron_set_env('YII_ENABLE_ERROR_HANDLER', 'false');
klytron_set_env('YII_ENABLE_EXCEPTION_HANDLER', 'false');
```

## 🎯 Yii2 Database Management

### Database Configuration

```php
// Configure Yii2 database
klytron_configure_project([
    'database' => 'mysql',
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'myyii2app',
    'db_user' => 'root',
    'db_password' => 'secret',
    'db_charset' => 'utf8',
    'db_tablePrefix' => '',
]);
```

### Database Migrations

```php
// Configure Yii2 migrations
klytron_configure_project([
    'database' => 'mysql',
    'migrations' => true,
    'migration_namespace' => 'console\\migrations',
    'migration_table' => '{{%migration}}',
    'migration_path' => 'console/migrations',
]);
```

### Migration Tasks

```php
// Add Yii2 migration tasks
klytron_add_task('deploy:yii2:migrate', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Running migrations for {$app}...</info>");
        run("php yii migrate/up --interactive=0 --app={$app}");
    }
}, [
    'description' => 'Run Yii2 database migrations',
]);

klytron_add_task('deploy:yii2:migrate:down', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Rolling back migrations for {$app}...</info>");
        run("php yii migrate/down 1 --interactive=0 --app={$app}");
    }
}, [
    'description' => 'Rollback Yii2 database migrations',
]);
```

## 🎯 Yii2 Asset Management

### Asset Configuration

```php
// Configure Yii2 assets
klytron_configure_project([
    'supports_assets' => true,
    'asset_compression' => true,
    'asset_optimization' => true,
    'asset_publishing' => true,
    'asset_combine' => true,
    'asset_minify' => true,
]);
```

### Asset Tasks

```php
// Add Yii2 asset tasks
klytron_add_task('deploy:yii2:assets', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Compiling assets for {$app}...</info>");
        
        // Compile assets
        run("php yii asset/compress --interactive=0 --app={$app}");
        
        // Publish assets
        run("php yii asset/publish --interactive=0 --app={$app}");
    }
}, [
    'description' => 'Compile Yii2 assets',
]);

klytron_add_task('deploy:yii2:assets:clear', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Clearing assets for {$app}...</info>");
        run("php yii asset/clear --interactive=0 --app={$app}");
    }
}, [
    'description' => 'Clear Yii2 assets',
]);
```

## 🎯 Yii2 Cache Management

### Cache Configuration

```php
// Configure Yii2 cache
klytron_configure_project([
    'supports_cache' => true,
    'cache_driver' => 'redis',
    'cache_optimization' => true,
    'cache_clear_on_deploy' => true,
    'cache_warming' => true,
]);
```

### Cache Tasks

```php
// Add Yii2 cache tasks
klytron_add_task('deploy:yii2:cache', function () {
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
}, [
    'description' => 'Configure Yii2 cache',
]);

klytron_add_task('deploy:yii2:cache:clear', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Clearing cache for {$app}...</info>");
        run("php yii cache/flush-all --interactive=0 --app={$app}");
    }
}, [
    'description' => 'Clear Yii2 cache',
]);
```

## 🎯 Yii2 Maintenance Mode

### Maintenance Configuration

```php
// Configure Yii2 maintenance mode
klytron_configure_project([
    'supports_maintenance' => true,
    'maintenance_mode' => true,
    'maintenance_message' => 'Site is under maintenance',
    'maintenance_retry' => 60,
    'maintenance_allowed_ips' => ['127.0.0.1', '::1'],
]);
```

### Maintenance Tasks

```php
// Add Yii2 maintenance tasks
klytron_add_task('deploy:yii2:maintenance:on', function () {
    $apps = get('yii2_apps', ['frontend']);
    $message = get('maintenance_message', 'Site is under maintenance');
    $retry = get('maintenance_retry', 60);
    
    foreach ($apps as $app) {
        writeln("<info>Enabling maintenance mode for {$app}...</info>");
        run("php yii maintenance/enable --message='{$message}' --retry={$retry} --app={$app}");
    }
}, [
    'description' => 'Enable Yii2 maintenance mode',
]);

klytron_add_task('deploy:yii2:maintenance:off', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Disabling maintenance mode for {$app}...</info>");
        run("php yii maintenance/disable --app={$app}");
    }
}, [
    'description' => 'Disable Yii2 maintenance mode',
]);
```

## 🎯 Yii2 Console Commands

### Console Configuration

```php
// Configure Yii2 console commands
klytron_configure_project([
    'supports_console' => true,
    'console_commands' => [
        'migrate/up',
        'cache/flush-all',
        'rbac/init',
        'user/create',
    ],
    'console_commands_per_app' => [
        'frontend' => ['migrate/up', 'cache/flush-all'],
        'backend' => ['rbac/init', 'user/create'],
    ],
]);
```

### Console Tasks

```php
// Add Yii2 console tasks
klytron_add_task('deploy:yii2:console', function () {
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
}, [
    'description' => 'Run Yii2 console commands',
]);
```

## 🎯 Yii2 Security

### Security Configuration

```php
// Configure Yii2 security
klytron_configure_project([
    'yii2_security' => true,
    'security_headers' => true,
    'csrf_validation' => true,
    'xss_protection' => true,
    'sql_injection_protection' => true,
]);
```

### Security Tasks

```php
// Add Yii2 security tasks
klytron_add_task('deploy:yii2:security', function () {
    $apps = get('yii2_apps', ['frontend']);
    
    foreach ($apps as $app) {
        writeln("<info>Configuring security for {$app}...</info>");
        
        // Configure security settings
        run("php yii security/configure --app={$app}");
        
        // Generate security keys
        run("php yii security/generate-keys --app={$app}");
    }
}, [
    'description' => 'Configure Yii2 security',
]);
```

## 🎯 Yii2 Health Checks

### Health Check Configuration

```php
// Configure Yii2 health checks
klytron_configure_project([
    'yii2_health_checks' => true,
    'health_check_endpoints' => [
        '/site/health',
        '/api/health',
        '/admin/health',
    ],
    'health_check_timeout' => 30,
]);
```

### Health Check Tasks

```php
// Add Yii2 health check tasks
klytron_add_task('deploy:yii2:health_check', function () {
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
}, [
    'description' => 'Run Yii2 health checks',
]);
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
    'yii2_app_type' => 'advanced',
    'yii2_apps' => ['frontend', 'backend'],
    'database' => 'mysql',
    'supports_maintenance' => true,
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
    'yii2_app_type' => 'advanced',
    'yii2_apps' => ['frontend', 'backend', 'api'],
    'database' => 'mysql',
    'db_host' => 'localhost',
    'db_name' => 'myyii2app',
    'db_user' => 'root',
    'db_password' => 'secret',
    'supports_maintenance' => true,
    'supports_assets' => true,
    'supports_cache' => true,
    'supports_console' => true,
    'maintenance_mode' => true,
    'asset_compression' => true,
    'cache_optimization' => true,
    'console_commands' => [
        'migrate/up',
        'cache/flush-all',
        'rbac/init',
    ],
    'yii2_env' => 'prod',
    'yii2_debug' => false,
]);

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
