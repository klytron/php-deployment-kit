# PHP Deployment Kit - Project Structure

This document provides a comprehensive overview of the PHP Deployment Kit structure and organization.

## Directory Structure

```
php-deployment-kit/
├── deployment-kit.php              # Main entry point
├── deployment-kit-core.php         # Core functions and helpers
├── klytron-tasks.php              # Core deployment tasks
├── composer.json                   # Package configuration
├── README.md                      # Main documentation
├── CHANGELOG.md                   # Version history
├── LICENSE                        # MIT License
├── SECURITY.md                    # Security policy
├── install.sh                     # Installation script
│
├── src/
│   ├── DeployerRecipe.php         # Deployer recipe integration
│   ├── Commands/                  # Laravel Artisan commands
│   │   ├── KlytronDbSearchReplaceCommand.php
│   │   ├── KlytronFileDeCrypterCommand.php
│   │   ├── KlytronFileEnCrypterCommand.php
│   │   ├── KlytronStorageLinkCommand.php
│   │   └── KlytronSqliteSetterCommand.php
│   ├── Exceptions/                # Custom exceptions
│   │   ├── AssetMappingException.php
│   │   ├── DeploymentException.php
│   │   └── (others)
│   ├── Services/                  # Business logic services
│   │   ├── AssetMappingService.php
│   │   ├── DeploymentMetricsService.php
│   │   ├── RetryService.php
│   │   └── (others)
│   ├── Tasks/                     # Deployment task classes
│   │   ├── AssetMappingTask.php
│   │   ├── SitemapTask.php
│   │   ├── ImageOptimizationTask.php
│   │   └── (others)
│   ├── Validators/                # Configuration validators
│   │   └── ConfigurationValidator.php
│   └── Providers/
│       └── PhpDeploymentKitServiceProvider.php
│
├── recipes/
│   ├── klytron-laravel-recipe.php   # Laravel-specific tasks
│   ├── klytron-yii2-recipe.php      # Yii2-specific tasks
│   ├── klytron-php-recipe.php       # Simple PHP tasks
│   ├── klytron-server-recipe.php    # Server configuration tasks
│   ├── laravel.php                   # Laravel recipe alias
│   └── yii2.php                     # Yii2 recipe alias
│
├── templates/
│   ├── deploy.php.template
│   ├── laravel-deploy.php.template
│   ├── api-project.php.template
│   └── simple-php.php.template
│
├── examples/
│   ├── laravel-basic-example.php
│   └── simple-php-example.php
│
├── docs/
│   ├── README.md
│   ├── installation.md
│   ├── quick-start.md
│   ├── structure.md
│   ├── configuration-reference.md
│   ├── function-reference.md
│   ├── task-reference.md
│   ├── customization.md
│   ├── best-practices.md
│   ├── troubleshooting.md
│   ├── faq.md
│   ├── backup-restore.md
│   ├── development-guide.md
│   ├── server-configuration.md
│   ├── package-migration.md
│   ├── api-reference.md
│   ├── FEATURES.md
│   ├── ERROR_HANDLING.md
│   ├── DYNAMIC_CONFIGURATION.md
│   ├── TESTING.md
│   └── frameworks/
│       ├── laravel.md
│       ├── yii2.md
│       ├── simple-php.md
│       └── api.md
│
└── tests/
```

## File Purposes

### Core Files

| File                      | Purpose                                            |
| ------------------------- | -------------------------------------------------- |
| `deployment-kit.php`      | Main entry point - include this in your deploy.php |
| `deployment-kit-core.php` | Core functions and helper configurations           |
| `klytron-tasks.php`       | Core deployment tasks and implementations          |

### Recipe Files

| File                                 | Purpose                                                    |
| ------------------------------------ | ---------------------------------------------------------- |
| `recipes/klytron-laravel-recipe.php` | Laravel-specific tasks, migrations, cache, storage         |
| `recipes/klytron-yii2-recipe.php`    | Yii2-specific tasks and configurations                     |
| `recipes/klytron-php-recipe.php`     | Simple PHP project tasks                                   |
| `recipes/klytron-server-recipe.php`  | Server configuration and provisioning tasks                |
| `recipes/laravel.php`                | Laravel recipe alias (includes klytron-laravel-recipe.php) |

### Source Files

| File | Purpose |
| --- | --- |
| `src/DeployerRecipe.php` | Deployer recipe integration |
| `src/Commands/*.php` | Laravel Artisan commands |
| `src/Exceptions/*.php` | Custom exception classes |
| `src/Services/*.php` | Business logic (metrics, retry, assets) |
| `src/Tasks/*.php` | Deployment task classes (AssetMappingTask, SitemapTask, etc.) |
| `src/Validators/*.php` | Configuration validation |
| `src/Providers/PhpDeploymentKitServiceProvider.php` | Laravel service provider |

## Usage

### Laravel Project

```php
<?php
namespace Deployer;

// Include the Klytron PHP Deployment Kit
require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';

// Include the Laravel Recipe
require __DIR__ . '/vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Configure your application
klytron_configure_app('my-app', 'git@github.com:user/my-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('yourdomain.com');
klytron_set_php_version('php8.3');

klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    'supports_vite' => true,
    'supports_storage_link' => true,
]);

klytron_configure_host('your-server.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);

task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'deploy:symlink',
    'deploy:cleanup',
])->desc('Deploy my application');
```

### Simple PHP Project

```php
<?php
namespace Deployer;

// Include only the framework-agnostic core
require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';

klytron_configure_app('my-php-app', 'git@github.com:user/my-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('myapp.com');

klytron_configure_host('myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);
```

## Composer Integration

```json
{
  "extra": {
    "laravel": {
      "providers": [
        "Klytron\\PhpDeploymentKit\\Providers\\PhpDeploymentKitServiceProvider"
      ]
    },
    "deployer": {
      "recipes": [
        "deployment-kit.php",
        "klytron-tasks.php",
        "recipes/klytron-laravel-recipe.php",
        "recipes/klytron-server-recipe.php",
        "recipes/klytron-php-recipe.php",
        "recipes/klytron-yii2-recipe.php",
        "recipes/laravel.php"
      ]
    }
  }
}
```

## Requirements

- PHP 8.1+
- Deployer 7.0+
- Git
- SSH access to deployment server

## Documentation

- [Quick Start Guide](quick-start.md)
- [Configuration Reference](configuration-reference.md)
- [Laravel Guide](frameworks/laravel.md)
