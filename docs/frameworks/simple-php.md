# 🚀 Simple PHP Deployment Guide

Complete guide for deploying simple PHP applications with Klytron Deployer. This guide covers basic PHP applications, static sites, simple scripts, and non-framework PHP projects.

## 🎯 Simple PHP Features

Klytron Deployer provides streamlined support for simple PHP applications:

- **Minimal Setup** - Simple configuration for basic PHP projects
- **File Management** - Basic file and directory management
- **No Database** - No database operations (unless configured)
- **Static Assets** - Static file handling and optimization
- **Custom Scripts** - Support for custom deployment scripts
- **Lightweight** - Minimal deployment overhead
- **Fast Deployment** - Quick deployment process
- **Flexible** - Easy customization for specific needs

## 🎯 Quick Start for Simple PHP

### 1. Install Klytron Deployer

```bash
composer require klytron/php-deployment-kit
```

### 2. Copy Simple PHP Template

```bash
cp vendor/klytron/php-deployment-kit/templates/simple-php.php.template deploy.php
```

### 3. Configure Your Simple PHP Project

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';

// Configure simple PHP application
klytron_configure_app('my-php-app', 'git@github.com:user/my-php-app.git');

// Set deployment paths
klytron_set_paths('/var/www', '/var/www/html');

// Configure simple PHP project
klytron_configure_project([
    'type' => 'simple-php',
    'database' => 'none',
    'supports_static' => true,
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

## 🎯 Simple PHP Configuration Options

### Basic Simple PHP Configuration

```php
klytron_configure_project([
    'type' => 'simple-php',                 // Simple PHP project type
    'database' => 'none',                   // No database required
    'supports_static' => true,              // Enable static file support
    'supports_custom_scripts' => false,     // Enable custom scripts
    'file_permissions' => '644',            // File permissions
    'directory_permissions' => '755',       // Directory permissions
    'backup_enabled' => false,              // Disable backups for simple projects
    'maintenance_mode' => false,            // Disable maintenance mode
]);
```

### Advanced Simple PHP Configuration

```php
klytron_configure_project([
    'type' => 'simple-php',
    'database' => 'none',
    'supports_static' => true,
    'supports_custom_scripts' => true,
    'file_permissions' => '644',
    'directory_permissions' => '755',
    'static_optimization' => true,
    'gzip_compression' => true,
    'cache_headers' => true,
    'security_headers' => true,
    'custom_deploy_script' => 'deploy.sh',
    'pre_deploy_commands' => [
        'npm install',
        'npm run build',
    ],
    'post_deploy_commands' => [
        'php scripts/cleanup.php',
        'php scripts/notify.php',
    ],
]);
```

## 🎯 Simple PHP-Specific Tasks

### Available Simple PHP Tasks

#### `deploy:simple_php`

Main simple PHP deployment task.

```bash
vendor/bin/dep deploy:simple_php
```

#### `deploy:simple_php:static`

Deploy static assets for simple PHP projects.

```bash
vendor/bin/dep deploy:simple_php:static
```

#### `deploy:simple_php:permissions`

Set file permissions for simple PHP projects.

```bash
vendor/bin/dep deploy:simple_php:permissions
```

#### `deploy:simple_php:custom_scripts`

Run custom deployment scripts.

```bash
vendor/bin/dep deploy:simple_php:custom_scripts
```

#### `deploy:simple_php:optimize`

Optimize simple PHP application.

```bash
vendor/bin/dep deploy:simple_php:optimize
```

## 🎯 File Management

### File Permissions Configuration

```php
// Configure file permissions
klytron_configure_project([
    'file_permissions' => '644',
    'directory_permissions' => '755',
    'executable_files' => ['deploy.sh', 'scripts/*.php'],
    'executable_permissions' => '755',
]);
```

### File Permission Tasks

```php
// Add file permission tasks
klytron_add_task('deploy:simple_php:set_permissions', function () {
    $filePerms = get('file_permissions', '644');
    $dirPerms = get('directory_permissions', '755');
    $execPerms = get('executable_permissions', '755');
    
    // Set file permissions
    run("find . -type f -exec chmod {$filePerms} {} \\;");
    
    // Set directory permissions
    run("find . -type d -exec chmod {$dirPerms} {} \\;");
    
    // Set executable permissions for specific files
    $executableFiles = get('executable_files', []);
    foreach ($executableFiles as $file) {
        run("chmod {$execPerms} {$file}");
    }
}, [
    'description' => 'Set file permissions for simple PHP project',
]);
```

## 🎯 Static Asset Management

### Static Asset Configuration

```php
// Configure static assets
klytron_configure_project([
    'supports_static' => true,
    'static_directories' => ['css', 'js', 'images', 'fonts'],
    'static_optimization' => true,
    'gzip_compression' => true,
    'cache_headers' => true,
    'static_cache_duration' => 86400, // 24 hours
]);
```

### Static Asset Tasks

```php
// Add static asset tasks
klytron_add_task('deploy:simple_php:optimize_static', function () {
    if (get('static_optimization', false)) {
        writeln('<info>Optimizing static assets...</info>');
        
        // Optimize CSS
        if (file_exists('css')) {
            run('find css -name "*.css" -exec minify {} -o {} \\;');
        }
        
        // Optimize JavaScript
        if (file_exists('js')) {
            run('find js -name "*.js" -exec minify {} -o {} \\;');
        }
        
        // Optimize images
        if (file_exists('images')) {
            run('find images -name "*.jpg" -exec jpegoptim --strip-all {} \\;');
            run('find images -name "*.png" -exec optipng -o5 {} \\;');
        }
        
        writeln('<info>Static asset optimization completed</info>');
    }
}, [
    'description' => 'Optimize static assets',
]);

klytron_add_task('deploy:simple_php:configure_static', function () {
    if (get('gzip_compression', false)) {
        // Configure gzip compression
        $gzipConfig = "
        <IfModule mod_deflate.c>
            AddOutputFilterByType DEFLATE text/plain
            AddOutputFilterByType DEFLATE text/html
            AddOutputFilterByType DEFLATE text/xml
            AddOutputFilterByType DEFLATE text/css
            AddOutputFilterByType DEFLATE application/xml
            AddOutputFilterByType DEFLATE application/xhtml+xml
            AddOutputFilterByType DEFLATE application/rss+xml
            AddOutputFilterByType DEFLATE application/javascript
            AddOutputFilterByType DEFLATE application/x-javascript
        </IfModule>
        ";
        
        // Write .htaccess for gzip compression
        run("echo '{$gzipConfig}' > .htaccess");
    }
    
    if (get('cache_headers', false)) {
        $cacheDuration = get('static_cache_duration', 86400);
        
        // Configure cache headers
        $cacheConfig = "
        <IfModule mod_expires.c>
            ExpiresActive on
            ExpiresByType text/css \"access plus {$cacheDuration} seconds\"
            ExpiresByType application/javascript \"access plus {$cacheDuration} seconds\"
            ExpiresByType image/png \"access plus {$cacheDuration} seconds\"
            ExpiresByType image/jpg \"access plus {$cacheDuration} seconds\"
            ExpiresByType image/jpeg \"access plus {$cacheDuration} seconds\"
            ExpiresByType image/gif \"access plus {$cacheDuration} seconds\"
        </IfModule>
        ";
        
        // Append to .htaccess
        run("echo '{$cacheConfig}' >> .htaccess");
    }
}, [
    'description' => 'Configure static asset handling',
]);
```

## 🎯 Custom Scripts

### Custom Script Configuration

```php
// Configure custom scripts
klytron_configure_project([
    'supports_custom_scripts' => true,
    'custom_deploy_script' => 'deploy.sh',
    'pre_deploy_commands' => [
        'npm install',
        'npm run build',
        'php scripts/prepare.php',
    ],
    'post_deploy_commands' => [
        'php scripts/cleanup.php',
        'php scripts/notify.php',
        'php scripts/log.php',
    ],
]);
```

### Custom Script Tasks

```php
// Add custom script tasks
klytron_add_task('deploy:simple_php:pre_deploy', function () {
    $commands = get('pre_deploy_commands', []);
    
    foreach ($commands as $command) {
        writeln("<info>Running pre-deploy command: {$command}</info>");
        run($command);
    }
}, [
    'description' => 'Run pre-deployment commands',
]);

klytron_add_task('deploy:simple_php:post_deploy', function () {
    $commands = get('post_deploy_commands', []);
    
    foreach ($commands as $command) {
        writeln("<info>Running post-deploy command: {$command}</info>");
        run($command);
    }
}, [
    'description' => 'Run post-deployment commands',
]);

klytron_add_task('deploy:simple_php:custom_script', function () {
    $script = get('custom_deploy_script');
    
    if ($script && file_exists($script)) {
        writeln("<info>Running custom deployment script: {$script}</info>");
        run("chmod +x {$script}");
        run("./{$script}");
    }
}, [
    'description' => 'Run custom deployment script',
]);
```

## 🎯 Security Configuration

### Security Headers Configuration

```php
// Configure security headers
klytron_configure_project([
    'security_headers' => true,
    'security_headers_config' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy' => "default-src 'self'",
    ],
]);
```

### Security Tasks

```php
// Add security tasks
klytron_add_task('deploy:simple_php:security_headers', function () {
    if (get('security_headers', false)) {
        $headers = get('security_headers_config', []);
        
        $securityConfig = "<IfModule mod_headers.c>\n";
        foreach ($headers as $header => $value) {
            $securityConfig .= "    Header always set {$header} \"{$value}\"\n";
        }
        $securityConfig .= "</IfModule>\n";
        
        // Write security headers to .htaccess
        run("echo '{$securityConfig}' >> .htaccess");
        
        writeln('<info>Security headers configured</info>');
    }
}, [
    'description' => 'Configure security headers',
]);
```

## 🎯 Optimization

### Optimization Configuration

```php
// Configure optimization
klytron_configure_project([
    'optimization_enabled' => true,
    'minify_html' => true,
    'minify_css' => true,
    'minify_js' => true,
    'optimize_images' => true,
    'remove_comments' => true,
    'combine_files' => false,
]);
```

### Optimization Tasks

```php
// Add optimization tasks
klytron_add_task('deploy:simple_php:optimize', function () {
    if (get('optimization_enabled', false)) {
        writeln('<info>Optimizing simple PHP application...</info>');
        
        if (get('minify_html', false)) {
            // Minify HTML files
            run('find . -name "*.html" -exec sed "s/[[:space:]]\+/ /g" {} \\;');
        }
        
        if (get('remove_comments', false)) {
            // Remove HTML comments
            run('find . -name "*.html" -exec sed "/<!--.*-->/d" {} \\;');
        }
        
        if (get('minify_css', false)) {
            // Minify CSS files
            run('find . -name "*.css" -exec minify {} -o {} \\;');
        }
        
        if (get('minify_js', false)) {
            // Minify JavaScript files
            run('find . -name "*.js" -exec minify {} -o {} \\;');
        }
        
        if (get('optimize_images', false)) {
            // Optimize images
            run('find . -name "*.jpg" -exec jpegoptim --strip-all {} \\;');
            run('find . -name "*.png" -exec optipng -o5 {} \\;');
        }
        
        writeln('<info>Optimization completed</info>');
    }
}, [
    'description' => 'Optimize simple PHP application',
]);
```

## 🎯 Health Checks

### Health Check Configuration

```php
// Configure health checks
klytron_configure_project([
    'health_checks_enabled' => true,
    'health_check_endpoints' => [
        '/',
        '/health.php',
        '/status.php',
    ],
    'health_check_timeout' => 30,
]);
```

### Health Check Tasks

```php
// Add health check tasks
klytron_add_task('deploy:simple_php:health_check', function () {
    if (get('health_checks_enabled', false)) {
        $endpoints = get('health_check_endpoints', ['/']);
        $timeout = get('health_check_timeout', 30);
        
        foreach ($endpoints as $endpoint) {
            try {
                writeln("<info>Checking health: {$endpoint}</info>");
                run("curl -f --max-time {$timeout} http://localhost{$endpoint} || exit 1");
                writeln("<info>✓ {$endpoint} is healthy</info>");
            } catch (Exception $e) {
                writeln("<error>✗ {$endpoint} health check failed</error>");
                throw $e;
            }
        }
    }
}, [
    'description' => 'Run health checks for simple PHP application',
]);
```

## 🎯 Simple PHP Deployment Examples

### Basic Simple PHP Deployment

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';

// Basic simple PHP configuration
klytron_configure_app('my-php-app', 'git@github.com:user/my-php-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('myapp.com');

klytron_configure_project([
    'type' => 'simple-php',
    'database' => 'none',
    'supports_static' => true,
]);

klytron_configure_host('myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);
```

### Advanced Simple PHP Deployment

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';

// Advanced simple PHP configuration
klytron_configure_app('my-advanced-php-app', 'git@github.com:user/my-php-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('myapp.com');

klytron_configure_project([
    'type' => 'simple-php',
    'database' => 'none',
    'supports_static' => true,
    'supports_custom_scripts' => true,
    'static_optimization' => true,
    'gzip_compression' => true,
    'cache_headers' => true,
    'security_headers' => true,
    'optimization_enabled' => true,
    'health_checks_enabled' => true,
    'pre_deploy_commands' => [
        'npm install',
        'npm run build',
    ],
    'post_deploy_commands' => [
        'php scripts/cleanup.php',
        'php scripts/notify.php',
    ],
]);

klytron_configure_host('myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
    'http_group' => 'www-data',
]);

// Configure shared files and directories
klytron_configure_shared_files([
    'config.php',
    '.htaccess',
]);

klytron_configure_shared_dirs([
    'uploads',
    'logs',
    'cache',
]);

klytron_configure_writable_dirs([
    'uploads',
    'logs',
    'cache',
]);
```

### Static Site Deployment

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';

// Static site configuration
klytron_configure_app('my-static-site', 'git@github.com:user/my-static-site.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('static.myapp.com');

klytron_configure_project([
    'type' => 'simple-php',
    'database' => 'none',
    'supports_static' => true,
    'static_optimization' => true,
    'gzip_compression' => true,
    'cache_headers' => true,
    'optimization_enabled' => true,
    'static_cache_duration' => 604800, // 7 days
]);

klytron_configure_host('static.myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);
```

### PHP Script Deployment

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';

// PHP script configuration
klytron_configure_app('my-php-scripts', 'git@github.com:user/my-php-scripts.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('scripts.myapp.com');

klytron_configure_project([
    'type' => 'simple-php',
    'database' => 'none',
    'supports_custom_scripts' => true,
    'custom_deploy_script' => 'deploy.sh',
    'executable_files' => ['scripts/*.php', 'deploy.sh'],
    'pre_deploy_commands' => [
        'php scripts/validate.php',
    ],
    'post_deploy_commands' => [
        'php scripts/test.php',
        'php scripts/notify.php',
    ],
]);

klytron_configure_host('scripts.myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);
```

## 🎯 Simple PHP Best Practices

### File Organization Best Practices

1. **Clear Structure**: Organize files in logical directories
2. **Separate Concerns**: Keep different types of files separate
3. **Version Control**: Use proper .gitignore for sensitive files
4. **Documentation**: Include README files for complex projects
5. **Configuration**: Use external configuration files

### Performance Best Practices

1. **Static Optimization**: Optimize static assets (CSS, JS, images)
2. **Caching**: Implement proper caching headers
3. **Compression**: Enable gzip compression
4. **Minification**: Minify CSS and JavaScript files
5. **Image Optimization**: Optimize images for web

### Security Best Practices

1. **File Permissions**: Set appropriate file permissions
2. **Security Headers**: Implement security headers
3. **HTTPS**: Use HTTPS in production
4. **Input Validation**: Validate all user inputs
5. **Error Handling**: Don't expose sensitive information in errors

### Deployment Best Practices

1. **Testing**: Test deployment scripts locally
2. **Backup**: Backup important files before deployment
3. **Rollback**: Have a rollback strategy
4. **Monitoring**: Monitor application after deployment
5. **Documentation**: Document deployment procedures

## 🎯 Simple PHP Troubleshooting

### Common Simple PHP Issues

1. **Permission Errors**: Check file and directory permissions
2. **Static Asset Issues**: Verify static file paths and permissions
3. **Custom Script Errors**: Check script permissions and syntax
4. **Performance Issues**: Optimize static assets and enable caching
5. **Security Issues**: Verify security headers and HTTPS configuration

### Debugging Simple PHP Deployments

```bash
# Check file permissions
vendor/bin/dep run "ls -la"

# Check static assets
vendor/bin/dep run "ls -la css/ js/ images/"

# Test custom scripts
vendor/bin/dep run "php scripts/test.php"

# Check web server configuration
vendor/bin/dep run "apache2ctl -S"

# Check error logs
vendor/bin/dep run "tail -f /var/log/apache2/error.log"

# Test health endpoints
vendor/bin/dep run "curl -f http://localhost/health.php"
```

## 🎯 Next Steps

- **Read the [Configuration Reference](../configuration-reference.md)** - Complete configuration options
- **Explore [Examples](../examples/)** - Real-world simple PHP deployment examples
- **Check [Best Practices](../best-practices.md)** - Simple PHP deployment best practices
- **Review [Task Reference](../task-reference.md)** - Available simple PHP tasks
