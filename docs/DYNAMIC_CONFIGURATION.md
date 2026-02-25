# Dynamic Configuration Guide

## Overview

The Klytron PHP Deployment Kit provides powerful dynamic configuration features that eliminate redundant configuration and make deployments more maintainable and flexible.

## 🚀 Dynamic Path Management

### Template-based Path Configuration

Instead of hardcoding paths, use templates with placeholders that are automatically resolved:

```php
// ✅ RECOMMENDED: Dynamic configuration with placeholders
klytron_set_paths(
    '/home/user/web-apps',                           // Parent directory
    '/var/www/${APP_URL_DOMAIN}/public_html'         // Template with placeholder
);

klytron_set_domain('myapp.com');                    // Resolves ${APP_URL_DOMAIN}
klytron_configure_app('my-app', 'git@github.com:user/my-app.git');

// ❌ AVOID: Static hardcoded paths
klytron_set_paths(
    '/home/user/web-apps',
    '/var/www/myapp.com/public_html'                  // Hardcoded, not flexible
);
```

### Automatic Path Generation

The deployment kit automatically generates paths using the following patterns:

#### Deploy Path
```php
// Automatically generated as: {{deploy_path_parent}}/{{application}}
// Result: /home/user/web-apps/my-app
```

#### Public HTML Path
```php
// Template: /var/www/${APP_URL_DOMAIN}/public_html
// After klytron_set_domain('myapp.com'): /var/www/myapp.com/public_html
```

## 📝 Available Placeholders

### `${APP_URL_DOMAIN}`
- **Purpose**: Domain-based path resolution
- **Source**: `klytron_set_domain()`
- **Example**: `/var/www/${APP_URL_DOMAIN}/public_html` → `/var/www/myapp.com/public_html`

### `{{application}}`
- **Purpose**: Application name in paths
- **Source**: `klytron_configure_app()`
- **Example**: `{{deploy_path_parent}}/{{application}}` → `/home/apps/my-app`

### `{{deploy_path_parent}}`
- **Purpose**: Parent directory for deployments
- **Source**: `klytron_set_paths()`
- **Example**: `{{deploy_path_parent}}/{{application}}` → `/home/apps/my-app`

## 🎯 Best Practices

### 1. Use Domain Placeholders for Public HTML
```php
// ✅ GOOD: Domain-based template
klytron_set_paths(
    '/home/user/apps',
    '/var/www/${APP_URL_DOMAIN}/html'
);

// ❌ BAD: Hardcoded domain
klytron_set_paths(
    '/home/user/apps',
    '/var/www/myapp.com/html'
);
```

### 2. Let the Kit Generate Deploy Paths
```php
// ✅ GOOD: No deploy_path in host config
klytron_configure_host('server.example.com', [
    'remote_user' => 'deploy',
    // deploy_path automatically generated
]);

// ❌ BAD: Manually specifying deploy_path
klytron_configure_host('server.example.com', [
    'remote_user' => 'deploy',
    'deploy_path' => '/home/user/apps/my-app',  // Redundant
]);
```

### 3. Configure Domain Early
```php
// ✅ GOOD: Set domain before using it in paths
klytron_set_domain('myapp.com');
klytron_set_paths('/home/apps', '/var/www/${APP_URL_DOMAIN}/html');

// ❌ BAD: Domain set after path configuration
klytron_set_paths('/home/apps', '/var/www/${APP_URL_DOMAIN}/html');
klytron_set_domain('myapp.com');  // Too late for resolution
```

## 🔧 Configuration Examples

### Basic Laravel Application
```php
<?php
namespace Deployer;

require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';
require __DIR__ . '/vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Application configuration
klytron_configure_app('my-laravel-app', 'git@github.com:user/my-laravel-app.git');

// Dynamic path configuration
klytron_set_paths(
    '/home/deploy/apps',
    '/var/www/${APP_URL_DOMAIN}/public_html'
);

klytron_set_domain('myapp.com');

// Host configuration (paths auto-generated)
klytron_configure_host('server.example.com', [
    'remote_user' => 'deploy',
    'http_user' => 'www-data',
    'branch' => 'main',
]);

// Laravel-specific configuration
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    'supports_vite' => true,
    'supports_storage_link' => true,
]);

// Deployment flow
task('deploy', [
    'deploy:prepare',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:symlink',
    'klytron:laravel:deploy:finalize:complete',
])->desc('Deploy Laravel application');
```

### Multi-Environment Setup
```php
<?php
namespace Deployer;

require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';
require __DIR__ . '/vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Application configuration
klytron_configure_app('my-app', 'git@github.com:user/my-app.git');

// Dynamic paths with environment-specific templates
klytron_set_paths(
    '/home/user/apps',
    '/var/www/${APP_URL_DOMAIN}/public_html'
);

// Environment-specific domain configuration
if (getenv('DEPLOY_ENV') === 'production') {
    klytron_set_domain('myapp.com');
} elseif (getenv('DEPLOY_ENV') === 'staging') {
    klytron_set_domain('staging.myapp.com');
} else {
    klytron_set_domain('dev.myapp.com');
}

// Host configuration
klytron_configure_host('server.example.com', [
    'remote_user' => 'deploy',
    'http_user' => 'www-data',
    'branch' => 'main',
]);
```

### Multi-Project Setup
```php
<?php
namespace Deployer;

require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';
require __DIR__ . '/vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Project 1: Main Application
klytron_configure_app('main-app', 'git@github.com:user/main-app.git');
klytron_set_paths('/home/user/apps', '/var/www/${APP_URL_DOMAIN}/html');
klytron_set_domain('mainapp.com');
klytron_configure_host('server1.example.com', ['remote_user' => 'deploy']);

// Project 2: API Service
klytron_configure_app('api-service', 'git@github.com:user/api-service.git');
klytron_set_paths('/home/user/apps', '/var/www/${APP_URL_DOMAIN}/html');
klytron_set_domain('api.mainapp.com');
klytron_configure_host('server2.example.com', ['remote_user' => 'deploy']);

// Project 3: Admin Panel
klytron_configure_app('admin-panel', 'git@github.com:user/admin-panel.git');
klytron_set_paths('/home/user/apps', '/var/www/${APP_URL_DOMAIN}/html');
klytron_set_domain('admin.mainapp.com');
klytron_configure_host('server1.example.com', ['remote_user' => 'deploy']);
```

## 🔄 Migration Guide

### From Static to Dynamic Configuration

#### Before (Static)
```php
klytron_configure_app('my-app', 'git@github.com:user/my-app.git');
klytron_set_paths('/home/user/apps', '/var/www/myapp.com/html');
klytron_configure_host('server.example.com', [
    'deploy_path' => '/home/user/apps/my-app',
    'public_html' => '/var/www/myapp.com/html',
]);
```

#### After (Dynamic)
```php
klytron_configure_app('my-app', 'git@github.com:user/my-app.git');
klytron_set_paths('/home/user/apps', '/var/www/${APP_URL_DOMAIN}/html');
klytron_set_domain('myapp.com');
klytron_configure_host('server.example.com', [
    // deploy_path and public_html auto-generated
]);
```

### Benefits of Migration
- ✅ **Single Source of Truth**: Domain defined once, used everywhere
- ✅ **Easy Domain Changes**: Update `klytron_set_domain()` and paths update automatically
- ✅ **Environment Flexibility**: Same config works for staging, production, etc.
- ✅ **Reduced Duplication**: No more redundant path specifications

## 🛠️ Advanced Features

### Variable Interpolation
The deployment kit supports environment variable interpolation:

```php
// In .env file
// APP_URL_DOMAIN=myapp.com
// DEPLOY_PATH_PARENT=/home/user/apps

// In deploy.php
klytron_set_paths(
    '${DEPLOY_PATH_PARENT}',
    '/var/www/${APP_URL_DOMAIN}/html'
);
```

### Custom Placeholders
You can create custom configuration variables:

```php
// Set custom variable
set('custom_project_name', 'my-awesome-project');

// Use in paths (if supported by your custom tasks)
set('custom_path', '/home/user/${custom_project_name}');
```

## 📋 Configuration Checklist

### ✅ Recommended Configuration
- [ ] Use `${APP_URL_DOMAIN}` placeholder in public HTML paths
- [ ] Let deploy paths auto-generate (don't specify in host config)
- [ ] Set domain before using it in path templates
- [ ] Use descriptive application names
- [ ] Configure parent directory once in `klytron_set_paths()`

### ❌ Common Pitfalls to Avoid
- [ ] Don't hardcode domains in path configurations
- [ ] Don't specify `deploy_path` in host configuration
- [ ] Don't set domain after path configuration
- [ ] Don't duplicate path information in multiple places

## 🔍 Troubleshooting

### Placeholder Not Resolved
```php
// Problem: ${APP_URL_DOMAIN} not replaced
klytron_set_paths('/home/apps', '/var/www/${APP_URL_DOMAIN}/html');
// Missing: klytron_set_domain('myapp.com');

// Solution: Set domain before or after path configuration
klytron_set_domain('myapp.com');
klytron_set_paths('/home/apps', '/var/www/${APP_URL_DOMAIN}/html');
```

### Deploy Path Not Generated
```php
// Problem: deploy_path not auto-generated
klytron_configure_host('server.com', [
    'deploy_path' => '/custom/path',  // This overrides auto-generation
]);

// Solution: Remove deploy_path from host config
klytron_configure_host('server.com', [
    // deploy_path auto-generated as {{deploy_path_parent}}/{{application}}
]);
```

### Path Resolution Issues
```php
// Debug path resolution
task('debug:paths', function () {
    info('Application: ' . get('application'));
    info('Domain: ' . get('domain'));
    info('Deploy Path Parent: ' . get('deploy_path_parent'));
    info('Deploy Path: ' . get('deploy_path'));
    info('Public HTML: ' . get('application_public_html'));
});
```

---

## 📚 Additional Resources

- [Features Guide](FEATURES.md) - Overview of all features
- [Quick Start Guide](quick-start.md) - Getting started tutorial
- [Configuration Reference](configuration-reference.md) - Detailed configuration options
- [Examples Directory](../examples/) - Real-world configuration examples

By following these dynamic configuration best practices, you'll create more maintainable, flexible, and reusable deployment configurations!
