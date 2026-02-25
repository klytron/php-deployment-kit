# ❓ Frequently Asked Questions (FAQ)

[← Back to Documentation](README.md)

## 📋 Table of Contents

- [General Questions](#general-questions)
- [Installation & Setup](#installation--setup)
- [Configuration](#configuration)
- [Deployment Issues](#deployment-issues)
- [Framework-Specific](#framework-specific)
- [Troubleshooting](#troubleshooting)
- [Performance & Optimization](#performance--optimization)
- [Security](#security)
- [Support & Community](#support--community)

## 🤔 General Questions

### What is Klytron Deployer?

**Klytron Deployer** is a comprehensive, production-ready deployment library for PHP applications. It provides enhanced features, safety checks, and monitoring capabilities for deploying Laravel, Yii2, API projects, and simple PHP applications with zero-configuration setup.

### How is it different from regular Deployer?

Klytron Deployer extends the standard Deployer framework with:
- **Smart auto-detection** of project types and frameworks
- **Enhanced safety features** with multiple validation layers
- **Automatic backup integration** with rollback capabilities
- **Framework-specific optimizations** for Laravel, Yii2, and PHP
- **Production-ready defaults** with minimal configuration
- **Comprehensive monitoring** and health checks

### What frameworks does it support?

- **Laravel** - Full support with migrations, Passport, Vite, Mix
- **Laravel API** - API-specific optimizations with Passport auth
- **Yii2** - Advanced template with maintenance mode
- **Simple PHP** - Minimal deployment for basic PHP applications
- **Other PHP** - Extensible for custom frameworks

### Is it free to use?

Yes! Klytron Deployer is open-source and available under the MIT License. You can use it for personal and commercial projects without any restrictions.

## 📦 Installation & Setup

### How do I install Klytron Deployer?

```bash
# Install via Composer (recommended)
composer require klytron/php-deployment-kit

# Or install globally
composer global require klytron/php-deployment-kit
```

### What are the system requirements?

- **PHP**: 8.1 or higher
- **Deployer**: 7.0 or higher
- **Git**: For repository access
- **SSH**: For server access
- **Composer**: For dependency management

### How do I set up my first deployment?

```bash
# 1. Install the package
composer require klytron/php-deployment-kit

# 2. Copy a template
cp vendor/klytron/php-deployment-kit/templates/laravel-deploy.php.template deploy.php

# 3. Configure your settings
# Edit deploy.php with your project details

# 4. Test configuration
vendor/bin/dep test

# 5. Deploy
vendor/bin/dep deploy
```

### Do I need to install Deployer separately?

No! Klytron Deployer includes Deployer as a dependency, so it's automatically installed when you install Klytron Deployer.

## ⚙️ Configuration

### How do I configure my application?

```php
// Basic configuration
klytron_configure_app('my-app', 'git@github.com:user/my-app.git');

// With options
klytron_configure_app('my-app', 'git@github.com:user/my-app.git', [
    'keep_releases' => 5,
    'default_timeout' => 1800,
]);
```

### How do I set up server configuration?

```php
// Configure host
klytron_configure_host('your-server.com', [
    'remote_user' => 'deploy',
    'http_user' => 'www-data',
    'http_group' => 'www-data',
]);
```

### How do I configure database settings?

```php
// Configure project with database
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
]);
```

### Can I use different configurations for different environments?

Yes! You can configure multiple environments:

```php
// Production
klytron_configure_host('prod.example.com', [
    'remote_user' => 'deploy',
    'labels' => ['stage' => 'production'],
]);

// Staging
klytron_configure_host('staging.example.com', [
    'remote_user' => 'deploy',
    'labels' => ['stage' => 'staging'],
]);
```

## 🚀 Deployment Issues

### My deployment is hanging. What should I do?

1. **Check SSH connection**:
   ```bash
   ssh user@your-server.com
   ```

2. **Check server resources**:
   ```bash
   df -h  # Check disk space
   free -h  # Check memory
   ```

3. **Check deployment logs**:
   ```bash
   vendor/bin/dep deploy --verbose
   ```

4. **Common causes**:
   - Insufficient disk space
   - Memory issues
   - Network connectivity problems
   - Permission issues

### How do I rollback a failed deployment?

```bash
# Rollback to previous release
vendor/bin/dep rollback

# Rollback to specific release
vendor/bin/dep rollback 2024-01-15-10-30-00
```

### My assets aren't compiling. What's wrong?

For Laravel projects with Vite:

```php
// Make sure Vite is enabled
klytron_configure_project([
    'supports_vite' => true,
]);
```

For Laravel Mix:

```php
// Enable Mix support
klytron_configure_project([
    'supports_mix' => true,
]);
```

### How do I enable zero-downtime deployments?

Zero-downtime deployments are enabled by default. The deployment process:
1. Creates a new release directory
2. Updates code and dependencies
3. Runs migrations and optimizations
4. Switches the symlink atomically

### My deployment fails with permission errors. How do I fix this?

```php
// Configure proper permissions
klytron_configure_host('your-server.com', [
    'remote_user' => 'deploy',
    'http_user' => 'www-data',
    'writable_mode' => 'chmod',
    'writable_use_sudo' => true,
]);
```

## 🏗️ Framework-Specific

### Laravel

#### How do I configure Laravel Passport?

```php
klytron_configure_project([
    'type' => 'laravel',
    'supports_passport' => true,
]);
```

#### How do I enable Vite build support?

```php
klytron_configure_project([
    'type' => 'laravel',
    'supports_vite' => true,
]);
```

#### How do I configure Laravel storage links?

```php
klytron_configure_project([
    'type' => 'laravel',
    'supports_storage_link' => true,
]);
```

### Yii2

#### How do I configure Yii2 maintenance mode?

Maintenance mode is automatically handled during deployment. You can also manually control it:

```bash
# Enable maintenance mode
vendor/bin/dep yii:maintenance:enable

# Disable maintenance mode
vendor/bin/dep yii:maintenance:disable
```

### Simple PHP

#### How do I configure a simple PHP project?

```php
klytron_configure_project([
    'type' => 'simple-php',
    'web_root' => 'public',
    'index_file' => 'index.php',
]);
```

## 🔧 Troubleshooting

### "Permission denied" errors

**Solution**: Configure proper user permissions:

```php
klytron_configure_host('your-server.com', [
    'remote_user' => 'deploy', // Use dedicated deploy user
    'http_user' => 'www-data',
    'writable_mode' => 'chmod',
    'writable_use_sudo' => true,
]);
```

### "Repository not found" errors

**Solution**: Check your repository configuration:

```php
// Make sure the repository URL is correct
klytron_configure_app('my-app', 'git@github.com:user/my-app.git');

// Or use HTTPS
klytron_configure_app('my-app', 'https://github.com/user/my-app.git');
```

### "Database connection failed" errors

**Solution**: Verify database configuration:

```php
// Check your .env file has correct database settings
// Make sure the database server is accessible
// Verify database user permissions
```

### "Composer install failed" errors

**Solution**: Check Composer configuration:

```bash
# Test Composer manually
composer install --no-dev --optimize-autoloader

# Check PHP memory limit
php -d memory_limit=-1 composer install
```

### "SSH key not found" errors

**Solution**: Set up SSH keys properly:

```bash
# Generate SSH key
ssh-keygen -t rsa -b 4096 -C "your-email@example.com"

# Add to SSH agent
ssh-add ~/.ssh/id_rsa

# Copy to server
ssh-copy-id user@your-server.com
```

## ⚡ Performance & Optimization

### How can I speed up deployments?

1. **Use shared directories** for persistent files
2. **Enable Composer optimization**:
   ```php
   klytron_configure_app('my-app', 'repo-url', [
       'composer_options' => '--no-dev --optimize-autoloader',
   ]);
   ```
3. **Use parallel deployments** for multiple servers
4. **Optimize asset compilation** (Vite/Mix)

### How do I reduce deployment size?

1. **Exclude unnecessary files**:
   ```php
   klytron_configure_exclude([
       'node_modules',
       'tests',
       'docs',
       '.git',
   ]);
   ```
2. **Use .gitignore** to exclude files from repository
3. **Optimize Composer dependencies**

### How do I monitor deployment performance?

```bash
# Enable verbose output
vendor/bin/dep deploy --verbose

# Check deployment logs
vendor/bin/dep deploy:log

# Monitor server resources during deployment
```

## 🔒 Security

### How do I secure my deployment configuration?

1. **Use SSH keys** instead of passwords
2. **Restrict file permissions**:
   ```bash
   chmod 600 deploy.php
   ```
3. **Use environment variables** for sensitive data
4. **Regular security updates**

### How do I handle sensitive data in deployment?

```php
// Use environment variables
klytron_configure_app('my-app', $_ENV['REPO_URL']);

// Or use separate config files
klytron_configure_app('my-app', 'repo-url', [
    'env_file' => '.env.production',
]);
```

### How do I secure database credentials?

1. **Use environment files** (.env)
2. **Restrict database user permissions**
3. **Use connection encryption** (SSL/TLS)
4. **Regular credential rotation**

## 🆘 Support & Community

### Where can I get help?

- **Documentation**: [docs/](README.md) - Comprehensive guides
- **GitHub Issues**: [Report bugs](https://github.com/klytron/php-deployment-kit/issues)
- **GitHub Discussions**: [Ask questions](https://github.com/klytron/php-deployment-kit/discussions)
- **Stack Overflow**: [Community Q&A](https://stackoverflow.com/questions/tagged/klytron-php-deployment-kit)

### How do I report a bug?

1. **Check existing issues** on GitHub
2. **Create a new issue** with:
   - Clear description of the problem
   - Steps to reproduce
   - Expected vs actual behavior
   - Environment details (PHP version, OS, etc.)
   - Relevant logs or error messages

### How can I contribute?

1. **Fork the repository**
2. **Create a feature branch**
3. **Make your changes**
4. **Add tests** if applicable
5. **Submit a pull request**

### Where can I find examples?

- **[Examples](../examples/)** - Real-world deployment examples
- **[Templates](../templates/)** - Ready-to-use templates
- **[Working Scripts](../working-deploy-scripts/)** - Production scripts

### How do I stay updated?

- **Watch the repository** on GitHub
- **Follow releases** for new features
- **Check the changelog** for updates
- **Join discussions** for community updates

---

**🔍 Still Need Help?**: Create an issue on GitHub with detailed information about your problem.
