# Klytron Deployer Examples

This directory contains real-world deployment examples based on working projects. These examples demonstrate best practices and different deployment scenarios.

## 📋 Available Examples

### 🚀 Laravel Examples

#### [Basic Laravel Example](laravel-basic-example.php)
A standard Laravel application deployment with MySQL database.

**Features:**
- Standard Laravel setup
- MySQL database support
- Basic shared files and directories
- Standard deployment flow

**Use Case:** Perfect for most Laravel applications

#### [Laravel with Vite Example](laravel-vite-example.php) *(Coming Soon)*
Laravel application with Vite for asset compilation.

**Features:**
- Vite build support
- Node.js integration
- Asset compilation
- Modern frontend workflow

**Use Case:** Laravel applications using Vite for frontend assets

#### [Laravel API Example](laravel-api-example.php) *(Coming Soon)*
Laravel API application with Passport authentication.

**Features:**
- Laravel Passport support
- API-specific configuration
- Rate limiting setup
- API documentation generation

**Use Case:** Laravel API applications with authentication

### 🗄️ Database Examples

#### [SQLite Laravel Example](laravel-sqlite-example.php) *(Coming Soon)*
Laravel application using SQLite database.

**Features:**
- SQLite database support
- Lightweight deployment
- No external database required
- Perfect for small applications

**Use Case:** Small Laravel applications or prototypes

#### [PostgreSQL Laravel Example](laravel-postgresql-example.php) *(Coming Soon)*
Laravel application with PostgreSQL database.

**Features:**
- PostgreSQL database support
- Advanced database features
- JSON column support
- Full-text search capabilities

**Use Case:** Laravel applications requiring PostgreSQL features

### 🌐 Framework Examples

#### [Yii2 Example](yii2-example.php) *(Coming Soon)*
Yii2 Advanced Application deployment.

**Features:**
- Yii2 framework support
- Multi-app structure
- Advanced template system
- Maintenance mode support

**Use Case:** Yii2 applications

#### [Simple PHP Example](simple-php-example.php) *(Coming Soon)*
Simple PHP application deployment.

**Features:**
- Minimal PHP setup
- No framework dependencies
- Basic file management
- Simple deployment flow

**Use Case:** Simple PHP applications or static sites

### 🔧 Advanced Examples

#### [Multi-Environment Example](multi-environment-example.php) *(Coming Soon)*
Deployment with multiple environments (staging, production).

**Features:**
- Environment-specific configurations
- Staging and production setups
- Different database configurations
- Environment-specific features

**Use Case:** Projects requiring multiple deployment environments

#### [Custom Tasks Example](custom-tasks-example.php) *(Coming Soon)*
Example with custom deployment tasks.

**Features:**
- Custom task creation
- Task hooks and dependencies
- Integration with external services
- Advanced deployment workflows

**Use Case:** Projects requiring custom deployment logic

## 🎯 How to Use Examples

### 1. Choose the Right Example

Select an example that matches your project type and requirements:

```bash
# For a basic Laravel project
cp examples/laravel-basic-example.php deploy.php

# For a Laravel project with Vite
cp examples/laravel-vite-example.php deploy.php

# For a simple PHP project
cp examples/simple-php-example.php deploy.php
```

### 2. Customize the Configuration

Edit the `deploy.php` file with your project-specific settings:

```php
// Update application name and repository
klytron_configure_app(
    'your-project-name',                    // Your application name
    'git@github.com:user/your-project.git', // Your repository URL
    [
        'keep_releases' => 3,               // Number of releases to keep
        'default_timeout' => 1800,          // Deployment timeout
    ]
);

// Update deployment paths
klytron_set_paths(
    '/your/parent/directory',               // Your parent directory
    '/your/public/html/path'                // Your public HTML path
);

// Update domain
klytron_set_domain('your-domain.com');

// Update host configuration
klytron_configure_host('your-server.com', [
    'remote_user' => 'your-user',
    'http_user' => 'your-web-user',
    'http_group' => 'your-web-group',
]);
```

### 3. Test Your Configuration

```bash
# Test the configuration
vendor/bin/dep test

# Run a dry deployment
vendor/bin/dep deploy --dry-run
```

### 4. Deploy Your Project

```bash
# Deploy your project
vendor/bin/dep deploy
```

## 🔍 Example Structure

Each example follows a consistent structure:

```php
<?php
/**
 * Example Name and Description
 *
 * @package ExamplePackage
 * @author Michael K. Laweh (klytron)
 */

namespace Deployer;

///////////////////////////////////////////////////////////////////////////////
// INCLUDE KLYTRON DEPLOYER LIBRARY
///////////////////////////////////////////////////////////////////////////////

require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

///////////////////////////////////////////////////////////////////////////////
// PROJECT-SPECIFIC CONFIGURATION
///////////////////////////////////////////////////////////////////////////////

// Application configuration
klytron_configure_app('app-name', 'repository-url', [...]);

// Path configuration
klytron_set_paths('parent-dir', 'public-html-path');

// Project capabilities
klytron_configure_project([...]);

// Host configuration
klytron_configure_host('server.com', [...]);

///////////////////////////////////////////////////////////////////////////////
// AUTOMATED DEPLOYMENT CONFIGURATION
///////////////////////////////////////////////////////////////////////////////

// Auto-configuration settings for testing

///////////////////////////////////////////////////////////////////////////////
// SHARED FILES/DIRECTORIES
///////////////////////////////////////////////////////////////////////////////

// Shared files and directories configuration

///////////////////////////////////////////////////////////////////////////////
// DEPLOYMENT FLOW
///////////////////////////////////////////////////////////////////////////////

// Deployment task definition

///////////////////////////////////////////////////////////////////////////////
// UTILITY TASKS
///////////////////////////////////////////////////////////////////////////////

// Test and help tasks

///////////////////////////////////////////////////////////////////////////////
// VALIDATION
///////////////////////////////////////////////////////////////////////////////

// Project-specific validation
```

## 📚 Learning from Examples

### Study Real-World Deployments

The examples in this directory are based on actual working deployments. Study them to understand:

1. **Configuration Patterns**: How different projects are configured
2. **Best Practices**: Recommended settings and approaches
3. **Common Patterns**: Reusable configuration patterns
4. **Troubleshooting**: How to handle common issues

### Adapt Examples to Your Needs

Don't copy examples blindly. Instead:

1. **Understand the Configuration**: Read through the comments
2. **Identify Relevant Parts**: Focus on sections that apply to your project
3. **Customize Appropriately**: Modify settings for your specific needs
4. **Test Thoroughly**: Always test before production deployment

## 🔗 Related Resources

- [Installation Guide](../docs/installation.md) - How to install Klytron Deployer
- [Configuration Guide](../docs/configuration.md) - Understanding configuration options
- [Function Reference](../docs/function-reference.md) - Complete function documentation
- [Working Scripts](../working-deploy-scripts/scyba-deployer-scripts/) - Real-world deployment scripts
- [Templates](../templates/) - Deployment script templates

## 🤝 Contributing Examples

We welcome contributions of new examples! To contribute:

1. **Create a New Example**: Based on a real working deployment
2. **Follow the Structure**: Use the consistent structure shown above
3. **Add Documentation**: Include clear comments and usage instructions
4. **Test Thoroughly**: Ensure the example works correctly
5. **Submit a Pull Request**: With your new example

## 📞 Support

- **Documentation**: [https://github.com/klytron/php-deployment-kit](https://github.com/klytron/php-deployment-kit)
- **Issues**: [GitHub Issues](https://github.com/klytron/php-deployment-kit/issues)
- **Author**: Michael K. Laweh (klytron) - [https://www.klytron.com](https://www.klytron.com) 