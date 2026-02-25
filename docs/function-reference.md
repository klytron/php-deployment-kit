# 🔧 Function Reference

[← Back to Documentation](README.md)

## 📋 Table of Contents

- [Overview](#overview)
- [🔧 Configuration Functions](#-configuration-functions)
- [🌐 Host Configuration](#-host-configuration)
- [📁 Path & Directory Functions](#-path--directory-functions)
- [🗄️ Database Functions](#-database-functions)
- [⚙️ Project Configuration](#-project-configuration)
- [🔄 Deployment Flow Functions](#-deployment-flow-functions)
- [🔍 Utility Functions](#-utility-functions)
- [📋 Framework-Specific Functions](#-framework-specific-functions)
- [🎯 Usage Examples](#-usage-examples)
- [⚠️ Common Mistakes](#-common-mistakes)

## 🌟 Overview

This document provides a comprehensive reference of all available functions in the Klytron Deployer library. These functions are designed to simplify deployment configuration and provide a consistent interface across different project types.

### 📚 Function Categories

- **Configuration Functions** - Basic application and project setup
- **Host Configuration** - Server and environment configuration
- **Path & Directory Functions** - File system and directory management
- **Database Functions** - Database configuration and management
- **Project Configuration** - Framework-specific settings
- **Deployment Flow Functions** - Custom deployment workflows
- **Utility Functions** - Helper functions and validations
- **Framework-Specific Functions** - Laravel, Yii2, and PHP-specific functions

## 🔧 Configuration Functions

### Application Configuration

#### `klytron_configure_app(string $name, string $repository, array $config = [])`
Configure the basic application settings.

```php
klytron_configure_app(
    'my-project',                           // Application name
    'git@github.com:user/my-project.git',  // Repository URL
    [
        'keep_releases' => 3,              // Number of releases to keep
        'default_timeout' => 1800,         // Deployment timeout
    ]
);
```

#### `klytron_configure_project(array $config)`
Configure project type and capabilities.

```php
klytron_configure_project([
    'type' => 'laravel',                   // Project type: laravel, yii2, simple-php
    'database' => 'mysql',                 // Database type: mysql, sqlite, postgresql, none
    'env_file_local' => '.env.production', // Local environment file
    'env_file_remote' => '.env',           // Remote environment file
    'supports_passport' => false,          // Laravel Passport support
    'supports_vite' => true,               // Vite build support
    'supports_storage_link' => true,       // Storage link support
]);
```

### Path Configuration

#### `klytron_set_paths(string $parentDir, string $publicHtml = '')`
Set deployment paths for parent directory and public HTML.

**Parameters:**
- `$parentDir` (string) - Parent directory for deployments
- `$publicHtml` (string) - Public HTML directory path

**Example:**
```php
klytron_set_paths(
    '/var/www',           // Parent directory
    '/var/www/html'       // Public HTML path
);
```

#### `klytron_set_public_dir(string $path)`
Set the public directory path for the application.

**Example:**
```php
klytron_set_public_dir('/var/www/html/public');
```

#### `klytron_set_shared_dir(string $path)`
Set the shared directory path for persistent files.

**Example:**
```php
klytron_set_shared_dir('/var/www/shared');
```

#### `klytron_get_public_html_path(): string`
Get the configured public HTML path.

**Returns:** (string) The public HTML path

**Example:**
```php
$publicPath = klytron_get_public_html_path();
echo "Public path: $publicPath";
```
Set the public directory path.

```php
klytron_set_public_dir('/var/www/html');
```

#### `klytron_set_shared_dir(string $path)`
Set the shared directory path for shared files.

```php
klytron_set_shared_dir('/var/www/shared');
```

#### `klytron_get_public_html_path(): string`
Get the current public HTML path.

```php
$publicHtmlPath = klytron_get_public_html_path();
```

### Host Configuration

#### `klytron_configure_host(string $hostname, array $config = [])`
Configure host with project-specific settings.

```php
klytron_configure_host('your-server.com', [
    'remote_user' => 'root',
    'branch' => 'main',
    'http_user' => 'www-data',
    'http_group' => 'www-data',
    'labels' => ['stage' => 'production'],
]);
```

#### `klytron_host(string $hostname, array $config = [])`
Alternative host configuration function.

```php
klytron_host('your-server.com', [
    'remote_user' => 'root',
    'branch' => 'main',
]);
```

### Environment Configuration

#### `klytron_set_php_version(string $version)`
Set the PHP version for deployment.

```php
klytron_set_php_version('php8.3');
```

#### `klytron_set_domain(string $domain)`
Set the application domain.

```php
klytron_set_domain('your-domain.com');
```

### Database Configuration

#### `klytron_configure_database(string $type, array $config = [])`
Configure database settings.

```php
klytron_configure_database('mysql', [
    'import_path' => 'database/live-db-exports',
    'supports_migrations' => true,
    'supports_seeders' => true,
]);
```

## 📁 File and Directory Management

### Shared Files and Directories

#### `klytron_configure_shared_files(array $files)`
Configure shared files for the project.

```php
klytron_configure_shared_files([
    '.env',
    'public/.htaccess',
    'storage/oauth-private.key',
]);
```

#### `klytron_configure_shared_dirs(array $dirs)`
Configure shared directories for the project.

```php
klytron_configure_shared_dirs([
    'storage',
    'public/uploads',
    'public/storage',
    'bootstrap/cache',
]);
```

#### `klytron_configure_writable_dirs(array $dirs)`
Configure writable directories for the project.

```php
klytron_configure_writable_dirs([
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/framework',
    'storage/logs',
]);
```

### Additive Functions (Add to existing configuration)

#### `klytron_add_shared_files(array $files)`
Add files to the existing shared files configuration.

```php
klytron_add_shared_files(['custom-file.txt']);
```

#### `klytron_add_shared_dirs(array $dirs)`
Add directories to the existing shared directories configuration.

```php
klytron_add_shared_dirs(['custom-dir']);
```

#### `klytron_add_writable_dirs(array $dirs)`
Add directories to the existing writable directories configuration.

```php
klytron_add_writable_dirs(['custom-writable-dir']);
```

## 🔍 Utility Functions

### Environment Functions

#### `klytron_getEnvValue($key, $envPath = '.env.production', $required = false)`
Get environment variable value from a file.

```php
$dbHost = klytron_getEnvValue('DB_HOST', '.env.production', true);
```

#### `validateRequiredEnvVars(array $requiredVars, $envPath = '.env.production')`
Validate that required environment variables exist.

```php
validateRequiredEnvVars(['DB_HOST', 'DB_NAME', 'APP_KEY'], '.env.production');
```

### Validation Functions

#### `klytron_validate_deploy_path_parent(): void`
Validate that deploy_path_parent is properly configured.

```php
klytron_validate_deploy_path_parent();
```



## 📋 Common Usage Patterns

### Laravel Project Setup

```php
// Basic Laravel configuration
klytron_configure_app('my-laravel-app', 'git@github.com:user/my-laravel-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_php_version('php8.3');
klytron_set_domain('myapp.com');

klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    'supports_passport' => false,
    'supports_vite' => true,
]);

klytron_configure_host('my-server.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);

klytron_configure_shared_files(['.env', 'public/.htaccess']);
klytron_configure_shared_dirs(['storage', 'public/uploads', 'bootstrap/cache']);
klytron_configure_writable_dirs(['storage', 'bootstrap/cache']);
```

## 🗄️ Database Functions

### Database Configuration

#### `klytron_configure_database(string $type, array $config = [])`

Configure database settings for the application.

**Parameters:**
- `$type` (string) - Database type: 'mysql', 'postgresql', 'sqlite', 'none'
- `$config` (array) - Database configuration options

**Configuration Options:**
- `host` (string) - Database host
- `port` (int) - Database port
- `database` (string) - Database name
- `username` (string) - Database username
- `password` (string) - Database password
- `charset` (string) - Database charset
- `backup_enabled` (bool) - Enable database backups
- `backup_compress` (bool) - Compress database backups
- `backup_options` (array) - Additional backup options

**Example:**
```php
klytron_configure_database('mysql', [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'myapp',
    'username' => 'dbuser',
    'password' => 'dbpass',
    'charset' => 'utf8mb4',
    'backup_enabled' => true,
    'backup_compress' => true,
    'backup_options' => [
        'include_tables' => ['users', 'posts'],
        'exclude_tables' => ['temp_*'],
    ],
]);
```

## ⚙️ Project Configuration

### PHP Version

#### `klytron_set_php_version(string $version)`

Set the PHP version for the deployment.

**Example:**
```php
klytron_set_php_version('php8.2');
```

### Domain Configuration

#### `klytron_set_domain(string $domain)`

Set the application domain for deployment configuration.

**Example:**
```php
klytron_set_domain('myapp.com');
```



## 🔍 Utility Functions

### Environment Functions

#### `klytron_getEnvValue(string $key, string $envPath = '.env.production', bool $required = false): string`

Get a value from an environment file.

**Parameters:**
- `$key` (string) - Environment variable key
- `$envPath` (string) - Path to environment file
- `$required` (bool) - Whether the value is required

**Returns:** (string) Environment variable value

**Example:**
```php
$dbHost = klytron_getEnvValue('DB_HOST', '.env.production', true);
$appName = klytron_getEnvValue('APP_NAME', '.env.production', false);
```

### Validation Functions

#### `klytron_validate_deploy_path_parent(): void`

Validate that the deployment parent path is properly configured.

**Throws:** Exception if validation fails

**Example:**
```php
try {
    klytron_validate_deploy_path_parent();
    echo "Deployment path is valid";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## 📋 Framework-Specific Functions

### Laravel Functions

#### `klytron_install_laravel_addons(): void`

Install Laravel-specific addons and packages.

**Example:**
```php
klytron_install_laravel_addons();
```

### Yii2 Functions

#### `klytron_configure_yii2_app(string $name, string $repository, array $config = [])`

Configure Yii2 application settings.

**Example:**
```php
klytron_configure_yii2_app('my-yii2-app', 'git@github.com:user/my-yii2-app.git', [
    'env_file' => '.env.production',
    'sqlite_file' => 'database.sqlite',
]);
```

#### `klytron_yii2_set_env_file(string $envFile)`

Set the Yii2 environment file.

**Example:**
```php
klytron_yii2_set_env_file('.env.production');
```

#### `klytron_yii2_set_sqlite_file(string $sqliteFile)`

Set the Yii2 SQLite database file.

**Example:**
```php
klytron_yii2_set_sqlite_file('database.sqlite');
```

#### `klytron_yii2_set_public_html(string $publicHtml)`

Set the Yii2 public HTML directory.

**Example:**
```php
klytron_yii2_set_public_html('/var/www/html/web');
```

#### `klytron_yii2_set_api_public_html(string $publicHtml)`

Set the Yii2 API public HTML directory.

**Example:**
```php
klytron_yii2_set_api_public_html('/var/www/html/web');
```
```

### Simple PHP Project Setup

```php
// Basic PHP configuration
klytron_configure_app('my-php-app', 'git@github.com:user/my-php-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_php_version('php8.3');

klytron_configure_project([
    'type' => 'simple-php',
    'database' => 'none',
]);

klytron_configure_host('my-server.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);
```

## 🎯 Usage Examples

### Laravel Application Setup

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Configure application
klytron_configure_app('my-laravel-app', 'git@github.com:user/my-app.git', [
    'keep_releases' => 5,
    'default_timeout' => 3600,
]);

// Set paths
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_php_version('php8.2');

// Configure project
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    'supports_vite' => true,
    'supports_storage_link' => true,
]);

// Configure database
klytron_configure_database('mysql', [
    'host' => 'localhost',
    'database' => 'myapp',
    'username' => 'dbuser',
    'password' => 'dbpass',
]);

// Configure host
klytron_configure_host('myapp.com', [
    'remote_user' => 'deploy',
    'http_user' => 'www-data',
    'ssh_multiplexing' => true,
]);

// Configure shared files and directories
klytron_configure_shared_files(['.env']);
klytron_configure_shared_dirs(['storage', 'public/uploads']);
klytron_configure_writable_dirs(['storage', 'bootstrap/cache']);
```

### API Project Setup

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Configure API application
klytron_configure_app('my-api', 'git@github.com:user/my-api.git');

// Configure for API project
klytron_configure_project([
    'type' => 'laravel-api',
    'database' => 'postgresql',
    'supports_passport' => true,
    'supports_rate_limiting' => true,
]);

// Configure database
klytron_configure_database('postgresql', [
    'host' => 'localhost',
    'database' => 'myapi',
    'username' => 'apiuser',
    'password' => 'apipass',
]);

// Configure host
klytron_configure_host('api.myapp.com', [
    'remote_user' => 'deploy',
    'http_user' => 'www-data',
]);
```

### Simple PHP Project Setup

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';

// Configure simple PHP application
klytron_configure_app('my-php-app', 'git@github.com:user/my-php-app.git');

// Configure for simple PHP project
klytron_configure_project([
    'type' => 'simple-php',
    'database' => 'none',
]);

// Configure host
klytron_configure_host('my-php-app.com', [
    'remote_user' => 'deploy',
    'http_user' => 'www-data',
]);

// Configure shared files
klytron_configure_shared_files(['config.php']);
klytron_configure_shared_dirs(['uploads']);
```

### Yii2 Application Setup

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-yii2-recipe.php';

// Configure Yii2 application
klytron_configure_yii2_app('my-yii2-app', 'git@github.com:user/my-yii2-app.git', [
    'env_file' => '.env.production',
]);

// Set Yii2-specific paths
klytron_yii2_set_env_file('.env.production');
klytron_yii2_set_public_html('/var/www/html/web');

// Configure host
klytron_configure_host('my-yii2-app.com', [
    'remote_user' => 'deploy',
    'http_user' => 'www-data',
]);
```

## ⚠️ Common Mistakes

### ❌ Don't Use These (They Don't Exist)

```php
klytron_set_public_html('/path/to/public_html');  // ❌ Function doesn't exist
klytron_set_web_root('/path/to/web');             // ❌ Function doesn't exist
klytron_configure_public_html('/path');           // ❌ Function doesn't exist
```

### ✅ Use These Instead

```php
klytron_set_paths('/parent/dir', '/path/to/public_html');  // ✅ Correct
klytron_set_public_dir('/path/to/public');                 // ✅ Correct
klytron_get_public_html_path();                            // ✅ Correct
klytron_configure_host('server.com', ['deploy_path' => '/path']); // ✅ Correct
```

### 🔧 Best Practices

1. **Always validate configuration** before deployment
2. **Use environment-specific settings** for different stages
3. **Configure backups** for production deployments
4. **Set proper permissions** for shared directories
5. **Use SSH multiplexing** for better performance
6. **Test configuration** in staging before production

## 📚 Related Documentation

- [Installation Guide](installation.md) - How to install and set up Klytron Deployer
- [Configuration Reference](configuration-reference.md) - All configuration options
- [Task Reference](task-reference.md) - Available deployment tasks (updated task naming)
- [Troubleshooting Guide](troubleshooting.md) - Common issues and solutions
- [Framework Guides](frameworks/) - Framework-specific deployment guides

---

**💡 Pro Tip**: Use the `--debug` flag with deployment commands to see detailed function execution information.

**🔍 Need Help?**: Check the [Troubleshooting Guide](troubleshooting.md) for common function-related issues. 