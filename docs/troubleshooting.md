# 🔧 Troubleshooting Guide

[← Back to Documentation](README.md)

## 📋 Table of Contents

- [Quick Diagnosis](#quick-diagnosis)
- [Common Issues](#common-issues)
- [Deployment Issues](#deployment-issues)
- [Database Issues](#database-issues)
- [SSH Issues](#ssh-issues)
- [Permission Issues](#permission-issues)
- [Configuration Issues](#configuration-issues)
- [Performance Issues](#performance-issues)
- [Debugging Tools](#debugging-tools)
- [Getting Help](#getting-help)

## 🚨 Quick Diagnosis

### Health Check Commands

```bash
# Test basic connectivity
vendor/bin/dep test

# Check configuration
vendor/bin/dep config:check

# Validate deployment setup
vendor/bin/dep validate

# Test SSH connection
vendor/bin/dep ssh:test

# Check server requirements
vendor/bin/dep server:check
```

### Emergency Commands

```bash
# Force deployment (skip checks)
vendor/bin/dep deploy --force

# Deploy with verbose output
vendor/bin/dep deploy -v

# Deploy with debug mode
vendor/bin/dep deploy --debug

# Rollback to previous version
vendor/bin/dep rollback
```

## ❌ Common Issues

### Issue: "Permission denied" errors

**Symptoms:**
- SSH connection fails
- File operations fail
- Deployment stops with permission errors

**Solutions:**

```bash
# Check SSH key permissions
chmod 600 ~/.ssh/id_rsa
chmod 644 ~/.ssh/id_rsa.pub

# Check server user permissions
vendor/bin/dep ssh:check-permissions

# Fix server permissions
vendor/bin/dep fix:permissions
```

**Configuration fix:**
```php
// In deploy.php
klytron_configure_host('your-server.com', [
    'remote_user' => 'deploy', // Use dedicated deploy user
    'http_user' => 'www-data',
    'writable_mode' => 'chmod',
    'writable_use_sudo' => true,
]);
```

### Issue: Database connection fails

**Symptoms:**
- Migration errors
- Backup failures
- Database-related deployment stops

**Solutions:**

```bash
# Test database connection
vendor/bin/dep db:test

# Check database credentials
vendor/bin/dep db:check-credentials

# Reset database connection
vendor/bin/dep db:reset
```

**Configuration fix:**
```php
// Verify database configuration
klytron_configure_database([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'myapp',
    'username' => 'dbuser',
    'password' => 'dbpass',
    'port' => 3306,
    'charset' => 'utf8mb4',
]);
```

### Issue: Composer dependencies fail

**Symptoms:**
- Composer install fails
- Package conflicts
- Memory limit errors

**Solutions:**

```bash
# Clear composer cache
vendor/bin/dep composer:clear-cache

# Update composer dependencies
vendor/bin/dep composer:update

# Install with memory limit
vendor/bin/dep composer:install --memory-limit=2G
```

**Configuration fix:**
```php
// In deploy.php
klytron_configure_composer([
    'memory_limit' => '2G',
    'timeout' => 300,
    'optimize' => true,
    'no_dev' => true,
]);
```

## 🚀 Deployment Issues

### Issue: Deployment hangs or times out

**Symptoms:**
- Deployment process stops responding
- Long-running operations
- Timeout errors

**Solutions:**

```bash
# Increase timeout
vendor/bin/dep deploy --timeout=600

# Deploy with progress monitoring
vendor/bin/dep deploy --progress

# Deploy in stages
vendor/bin/dep deploy:prepare
vendor/bin/dep deploy:code
vendor/bin/dep deploy:vendors
vendor/bin/dep deploy:publish
```

**Configuration fix:**
```php
// Increase timeouts
klytron_configure_deployment([
    'timeout' => 600,
    'ssh_multiplexing' => true,
    'ssh_type' => 'native',
    'ssh_arguments' => ['-o', 'ConnectTimeout=30'],
]);
```

### Issue: Asset compilation fails

**Symptoms:**
- Vite/Mix build errors
- Asset compilation timeouts
- Missing compiled assets

**Solutions:**

```bash
# Clear asset cache
vendor/bin/dep assets:clear

# Rebuild assets
vendor/bin/dep assets:build

# Check asset configuration
vendor/bin/dep assets:check
```

**Configuration fix:**
```php
// Configure asset compilation
klytron_configure_assets([
    'build_tool' => 'vite', // or 'mix'
    'build_command' => 'npm run build',
    'dev_command' => 'npm run dev',
    'timeout' => 300,
    'node_version' => '18',
]);
```

### Issue: Zero-downtime deployment fails

**Symptoms:**
- Application downtime during deployment
- Users see errors during deployment
- Maintenance mode issues

**Solutions:**

```bash
# Enable zero-downtime mode
vendor/bin/dep deploy --zero-downtime

# Check deployment strategy
vendor/bin/dep deploy:strategy

# Monitor deployment health
vendor/bin/dep deploy:health-check
```

**Configuration fix:**
```php
// Configure zero-downtime deployment
klytron_configure_deployment([
    'zero_downtime' => true,
    'health_check' => true,
    'health_check_url' => '/health',
    'health_check_timeout' => 30,
    'maintenance_mode' => false,
]);
```

## 🗄️ Database Issues

### Issue: Migration fails

**Symptoms:**
- Database migration errors
- Schema conflicts
- Migration rollback issues

**Solutions:**

```bash
# Check migration status
vendor/bin/dep db:migrate:status

# Run migrations with force
vendor/bin/dep db:migrate --force

# Rollback specific migration
vendor/bin/dep db:migrate:rollback --step=1

# Reset database
vendor/bin/dep db:reset
```

**Configuration fix:**
```php
// Configure database migrations
klytron_configure_database([
    'migrations' => [
        'enabled' => true,
        'force' => false,
        'timeout' => 300,
        'rollback_on_failure' => true,
    ],
]);
```

### Issue: Database backup fails

**Symptoms:**
- Backup creation errors
- Insufficient disk space
- Permission denied for backup

**Solutions:**

```bash
# Check disk space
vendor/bin/dep backup:check-space

# Test backup process
vendor/bin/dep backup:test

# Create backup with custom location
vendor/bin/dep backup:create --path=/tmp/backup
```

**Configuration fix:**
```php
// Configure backup settings
klytron_configure_backup([
    'backup_path' => '/var/backups',
    'compress' => true,
    'max_size' => '1G',
    'retention' => ['days' => 30],
]);
```

## 🔐 SSH Issues

### Issue: SSH connection fails

**Symptoms:**
- "Connection refused" errors
- SSH key authentication fails
- Host key verification fails

**Solutions:**

```bash
# Test SSH connection
vendor/bin/dep ssh:test

# Check SSH configuration
vendor/bin/dep ssh:check-config

# Generate new SSH key
ssh-keygen -t rsa -b 4096 -C "deploy@example.com"

# Add SSH key to server
vendor/bin/dep ssh:add-key
```

**Configuration fix:**
```php
// Configure SSH settings
klytron_configure_host('your-server.com', [
    'ssh_type' => 'native',
    'ssh_arguments' => [
        '-o', 'StrictHostKeyChecking=no',
        '-o', 'UserKnownHostsFile=/dev/null',
        '-o', 'ConnectTimeout=30',
    ],
    'ssh_multiplexing' => true,
]);
```

### Issue: SSH key not found

**Symptoms:**
- "No such file or directory" for SSH key
- Authentication fails
- Key path errors

**Solutions:**

```bash
# Check SSH key location
vendor/bin/dep ssh:check-keys

# Set custom SSH key path
vendor/bin/dep deploy --ssh-key=/path/to/key

# Generate SSH key
vendor/bin/dep ssh:generate-key
```

**Configuration fix:**
```php
// Specify SSH key path
klytron_configure_host('your-server.com', [
    'ssh_key_file' => '/path/to/private/key',
    'ssh_public_key_file' => '/path/to/public/key',
]);
```

## 📁 Permission Issues

### Issue: File permission errors

**Symptoms:**
- "Permission denied" for file operations
- Cannot write to directories
- Ownership issues

**Solutions:**

```bash
# Fix file permissions
vendor/bin/dep fix:permissions

# Check ownership
vendor/bin/dep check:ownership

# Set correct permissions
vendor/bin/dep set:permissions
```

**Configuration fix:**
```php
// Configure permissions
klytron_configure_host('your-server.com', [
    'writable_mode' => 'chmod',
    'writable_use_sudo' => true,
    'writable_dirs' => [
        'storage',
        'bootstrap/cache',
        'public/uploads',
    ],
    'http_user' => 'www-data',
    'http_group' => 'www-data',
]);
```

### Issue: Directory not writable

**Symptoms:**
- Cannot create directories
- Cannot write to storage
- Cache directory issues

**Solutions:**

```bash
# Make directories writable
vendor/bin/dep make:writable

# Create required directories
vendor/bin/dep create:directories

# Check directory permissions
vendor/bin/dep check:directories
```

## ⚙️ Configuration Issues

### Issue: Configuration validation fails

**Symptoms:**
- Configuration errors
- Missing required settings
- Invalid configuration values

**Solutions:**

```bash
# Validate configuration
vendor/bin/dep config:validate

# Check configuration
vendor/bin/dep config:check

# Generate configuration template
vendor/bin/dep config:generate
```

**Configuration fix:**
```php
// Ensure all required settings
klytron_configure_app('my-app', 'git@github.com:user/my-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_configure_host('your-server.com', [
    'remote_user' => 'deploy',
    'http_user' => 'www-data',
]);
```

### Issue: Environment-specific configuration

**Symptoms:**
- Wrong environment loaded
- Configuration conflicts
- Environment variables not set

**Solutions:**

```bash
# Check current environment
vendor/bin/dep env:check

# Set environment
vendor/bin/dep deploy --env=production

# Validate environment
vendor/bin/dep env:validate
```

**Configuration fix:**
```php
// Environment-specific configuration
klytron_configure_environment([
    'production' => [
        'database' => 'mysql',
        'cache' => 'redis',
        'queue' => 'redis',
    ],
    'staging' => [
        'database' => 'mysql',
        'cache' => 'file',
        'queue' => 'sync',
    ],
]);
```

## ⚡ Performance Issues

### Issue: Slow deployment

**Symptoms:**
- Deployment takes too long
- File transfer is slow
- Operations timeout

**Solutions:**

```bash
# Enable SSH multiplexing
vendor/bin/dep deploy --ssh-multiplexing

# Use parallel deployment
vendor/bin/dep deploy --parallel

# Optimize file transfer
vendor/bin/dep deploy --optimize
```

**Configuration fix:**
```php
// Performance optimization
klytron_configure_deployment([
    'ssh_multiplexing' => true,
    'parallel' => true,
    'optimize' => true,
    'shared_files' => ['.env'],
    'shared_dirs' => ['storage', 'public/uploads'],
    'writable_dirs' => ['storage', 'bootstrap/cache'],
]);
```

### Issue: Memory issues

**Symptoms:**
- Out of memory errors
- Composer fails
- Process killed

**Solutions:**

```bash
# Increase memory limit
vendor/bin/dep deploy --memory-limit=2G

# Clear caches
vendor/bin/dep cache:clear

# Optimize composer
vendor/bin/dep composer:optimize
```

## 🛠️ Debugging Tools

### Debug Commands

```bash
# Enable debug mode
vendor/bin/dep deploy --debug

# Show deployment info
vendor/bin/dep info

# Check server status
vendor/bin/dep server:status

# View deployment logs
vendor/bin/dep logs

# Test all components
vendor/bin/dep test:all
```

### Log Analysis

```bash
# View recent logs
vendor/bin/dep logs:recent

# Search logs
vendor/bin/dep logs:search "error"

# Export logs
vendor/bin/dep logs:export

# Clear logs
vendor/bin/dep logs:clear
```

### Health Monitoring

```bash
# Health check
vendor/bin/dep health:check

# Monitor deployment
vendor/bin/dep health:monitor

# Check system resources
vendor/bin/dep health:resources

# Validate deployment
vendor/bin/dep health:validate
```

## 🆘 Getting Help

### Self-Help Resources

- [Documentation](README.md) - Complete guides and references
- [Configuration Reference](configuration-reference.md) - All configuration options
- [Task Reference](task-reference.md) - Available commands and tasks
- [Examples](examples/) - Real-world usage examples

### Community Support

- [GitHub Issues](https://github.com/klytron/php-deployment-kit/issues) - Report bugs and request features
- [GitHub Discussions](https://github.com/klytron/php-deployment-kit/discussions) - Ask questions and share solutions
- [Stack Overflow](https://stackoverflow.com/questions/tagged/klytron-php-deployment-kit) - Community Q&A

### Professional Support

- **Author**: Michael K. Laweh (klytron)
- **Website**: [https://www.klytron.com](https://www.klytron.com)
- **Email**: hi@klytron.com

### Reporting Issues

When reporting issues, please include:

1. **Klytron Deployer version**: `vendor/bin/dep --version`
2. **PHP version**: `php --version`
3. **Operating system**: `uname -a`
4. **Error message**: Complete error output
5. **Configuration**: Relevant parts of your `deploy.php`
6. **Steps to reproduce**: Detailed steps to reproduce the issue

### Example Issue Report

```markdown
## Issue Description
Brief description of the problem

## Environment
- Klytron Deployer: 1.0.0
- PHP: 8.1.0
- OS: Ubuntu 20.04
- Server: CentOS 7

## Steps to Reproduce
1. Run `vendor/bin/dep deploy`
2. Error occurs at step X
3. See error message below

## Error Message
```
Complete error output here
```

## Configuration
```php
// Relevant configuration from deploy.php
```

## Expected Behavior
What should happen instead
```

---

**💡 Pro Tip**: Always test your deployment configuration in a staging environment before deploying to production.

**🔍 Debug Mode**: Use `--debug` flag with any command to get detailed output for troubleshooting.
