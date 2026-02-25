# 🔧 Customization Guide

This guide covers how to customize Klytron Deployer for your specific needs, create custom tasks, extend functionality, and adapt deployments for different project requirements.

## 🎯 Understanding Customization

Klytron Deployer is designed to be highly customizable while providing sensible defaults. You can customize:

- **Deployment workflows** - Modify the deployment process
- **Custom tasks** - Add your own deployment tasks
- **Hooks and events** - Execute code at specific points
- **Configuration** - Override default settings
- **Framework support** - Add support for new frameworks

## 🎯 Custom Tasks

### Creating Custom Tasks

Use the `klytron_add_task()` function to create custom tasks:

```php
// Basic custom task
klytron_add_task('deploy:custom', function () {
    run('echo "Running custom deployment task"');
    run('php artisan custom:command');
}, [
    'description' => 'Run custom deployment task',
    'dependencies' => ['deploy:update_code'],
]);
```

### Task with Parameters

Create tasks that accept parameters:

```php
klytron_add_task('deploy:custom_with_params', function () {
    $param1 = get('custom_param1', 'default_value');
    $param2 = get('custom_param2', 'default_value');
    
    run("echo 'Parameter 1: {$param1}'");
    run("echo 'Parameter 2: {$param2}'");
    
    // Use parameters in commands
    run("php artisan custom:command --param1={$param1} --param2={$param2}");
}, [
    'description' => 'Run custom task with parameters',
]);
```

### Task with Error Handling

Add proper error handling to your tasks:

```php
klytron_add_task('deploy:custom_safe', function () {
    try {
        writeln('<info>Starting custom task...</info>');
        
        // Your custom logic here
        run('php artisan custom:command');
        
        writeln('<info>Custom task completed successfully!</info>');
    } catch (Exception $e) {
        writeln('<error>Custom task failed: ' . $e->getMessage() . '</error>');
        throw $e;
    }
}, [
    'description' => 'Run custom task with error handling',
]);
```

### Conditional Tasks

Create tasks that run conditionally:

```php
klytron_add_task('deploy:conditional', function () {
    $stage = get('stage', 'production');
    $shouldRun = get('run_custom_task', false);
    
    if ($shouldRun && $stage === 'production') {
        writeln('<info>Running conditional task in production</info>');
        run('php artisan production:task');
    } else {
        writeln('<comment>Skipping conditional task</comment>');
    }
}, [
    'description' => 'Run conditional deployment task',
]);
```

## 🎯 Custom Hooks

### Adding Hooks

Hooks allow you to execute code at specific points in the deployment process:

```php
// Add hook to run after deployment
klytron_add_hook('after:deploy', 'deploy:custom');

// Add hook to run before deployment
klytron_add_hook('before:deploy', 'deploy:preparation');

// Add hook to run after code update
klytron_add_hook('after:deploy:update_code', 'deploy:post_update');
```

### Available Hook Points

- `before:deploy` - Before deployment starts
- `after:deploy` - After deployment completes
- `before:deploy:update_code` - Before code update
- `after:deploy:update_code` - After code update
- `before:deploy:vendors` - Before vendor installation
- `after:deploy:vendors` - After vendor installation
- `before:deploy:publish` - Before publishing
- `after:deploy:publish` - After publishing
- `before:deploy:shared` - Before shared files/dirs
- `after:deploy:shared` - After shared files/dirs
- `before:deploy:writable` - Before setting writable
- `after:deploy:writable` - After setting writable
- `before:deploy:symlink` - Before creating symlink
- `after:deploy:symlink` - After creating symlink
- `before:deploy:unlock` - Before unlocking
- `after:deploy:unlock` - After unlocking
- `before:deploy:cleanup` - Before cleanup
- `after:deploy:cleanup` - After cleanup

### Hook Examples

```php
// Pre-deployment hook
klytron_add_task('deploy:preparation', function () {
    writeln('<info>Preparing for deployment...</info>');
    
    // Check server resources
    $diskUsage = run('df -h / | tail -1 | awk \'{print $5}\' | sed \'s/%//\'');
    if ($diskUsage > 80) {
        throw new Exception('Disk usage is too high: ' . $diskUsage . '%');
    }
    
    // Check PHP version
    $phpVersion = run('php -v | head -1');
    writeln('<info>PHP Version: ' . $phpVersion . '</info>');
}, [
    'description' => 'Prepare deployment environment',
]);

// Post-deployment hook
klytron_add_task('deploy:post_deploy', function () {
    writeln('<info>Post-deployment tasks...</info>');
    
    // Clear application cache
    run('php artisan cache:clear');
    
    // Send notification
    run('curl -X POST https://hooks.slack.com/services/YOUR/WEBHOOK/URL -d "Deployment completed successfully!"');
    
    // Update deployment status
    run('echo "$(date): Deployment completed" >> /var/log/deployments.log');
}, [
    'description' => 'Post-deployment tasks',
]);

// Add hooks
klytron_add_hook('before:deploy', 'deploy:preparation');
klytron_add_hook('after:deploy', 'deploy:post_deploy');
```

## 🎯 Custom Deployment Flows

### Overriding Default Flow

You can override the default deployment flow:

```php
// Override the main deploy task
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'deploy:cleanup',
    'deploy:custom', // Add your custom task
]);
```

### Custom Deployment Strategy

Create a custom deployment strategy:

```php
// Blue-green deployment strategy
klytron_add_task('deploy:blue_green', function () {
    $currentRelease = get('current_release');
    $newRelease = get('new_release');
    
    // Deploy to new environment
    writeln('<info>Deploying to new environment...</info>');
    invoke('deploy:update_code');
    invoke('deploy:vendors');
    invoke('deploy:custom');
    
    // Health check
    writeln('<info>Running health checks...</info>');
    invoke('deploy:health_check');
    
    // Switch traffic
    writeln('<info>Switching traffic to new release...</info>');
    invoke('deploy:symlink');
    
    // Cleanup old release
    if ($currentRelease) {
        writeln('<info>Cleaning up old release...</info>');
        run("rm -rf {$currentRelease}");
    }
}, [
    'description' => 'Blue-green deployment strategy',
]);
```

## 🎯 Environment-Specific Customization

### Stage-Based Configuration

Customize behavior based on deployment stage:

```php
// Environment-specific configuration
$stage = get('stage', 'production');

if ($stage === 'production') {
    // Production-specific tasks
    klytron_add_task('deploy:production', function () {
        writeln('<info>Running production-specific tasks...</info>');
        
        // Backup before deployment
        invoke('deploy:database:backup');
        
        // Run production optimizations
        run('php artisan config:cache');
        run('php artisan route:cache');
        run('php artisan view:cache');
        
        // Set production environment
        run('php artisan env:production');
    });
    
    // Add to deployment flow
    klytron_add_hook('after:deploy:vendors', 'deploy:production');
    
} elseif ($stage === 'staging') {
    // Staging-specific tasks
    klytron_add_task('deploy:staging', function () {
        writeln('<info>Running staging-specific tasks...</info>');
        
        // Run tests
        run('php artisan test');
        
        // Generate test data
        run('php artisan db:seed --class=TestDataSeeder');
    });
    
    // Add to deployment flow
    klytron_add_hook('after:deploy:vendors', 'deploy:staging');
}
```

### Host-Specific Customization

Customize behavior based on host:

```php
// Host-specific tasks
klytron_add_task('deploy:host_specific', function () {
    $hostname = get('hostname');
    
    switch ($hostname) {
        case 'web1.myapp.com':
            writeln('<info>Running web server 1 specific tasks...</info>');
            run('php artisan queue:restart');
            break;
            
        case 'web2.myapp.com':
            writeln('<info>Running web server 2 specific tasks...</info>');
            run('php artisan cache:clear');
            break;
            
        case 'db.myapp.com':
            writeln('<info>Running database server specific tasks...</info>');
            run('php artisan migrate');
            break;
    }
}, [
    'description' => 'Host-specific deployment tasks',
]);

// Add to deployment flow
klytron_add_hook('after:deploy:symlink', 'deploy:host_specific');
```

## 🎯 Custom Framework Support

### Adding New Framework Support

Create custom framework support:

```php
// Custom framework recipe
klytron_add_task('deploy:custom_framework', function () {
    $framework = get('framework_type', 'custom');
    
    if ($framework === 'custom') {
        writeln('<info>Deploying custom framework...</info>');
        
        // Custom framework deployment steps
        run('composer install --no-dev --optimize-autoloader');
        run('npm install --production');
        run('npm run build');
        
        // Custom configuration
        run('cp config/production.php config/app.php');
        
        // Custom cache clearing
        run('rm -rf cache/*');
        run('rm -rf temp/*');
    }
}, [
    'description' => 'Deploy custom framework',
]);

// Add to deployment flow
klytron_add_hook('after:deploy:vendors', 'deploy:custom_framework');
```

### Framework Detection

Automatically detect and configure frameworks:

```php
// Framework detection
klytron_add_task('deploy:detect_framework', function () {
    $composerJson = get('composer_json', 'composer.json');
    
    if (file_exists($composerJson)) {
        $composer = json_decode(file_get_contents($composerJson), true);
        
        if (isset($composer['require']['laravel/framework'])) {
            set('framework_type', 'laravel');
            writeln('<info>Detected Laravel framework</info>');
        } elseif (isset($composer['require']['yiisoft/yii2'])) {
            set('framework_type', 'yii2');
            writeln('<info>Detected Yii2 framework</info>');
        } else {
            set('framework_type', 'custom');
            writeln('<info>Detected custom framework</info>');
        }
    } else {
        set('framework_type', 'simple');
        writeln('<info>No framework detected, using simple deployment</info>');
    }
}, [
    'description' => 'Detect application framework',
]);

// Add to deployment flow
klytron_add_hook('before:deploy:update_code', 'deploy:detect_framework');
```

## 🎯 Custom Configuration

### Dynamic Configuration

Create dynamic configuration based on environment:

```php
// Dynamic configuration
klytron_add_task('deploy:configure_dynamic', function () {
    $stage = get('stage', 'production');
    $hostname = get('hostname');
    
    // Dynamic database configuration
    if ($stage === 'production') {
        set('db_host', 'prod-db.myapp.com');
        set('db_name', 'myapp_prod');
    } else {
        set('db_host', 'dev-db.myapp.com');
        set('db_name', 'myapp_dev');
    }
    
    // Dynamic cache configuration
    if ($hostname === 'cache.myapp.com') {
        set('cache_driver', 'redis');
        set('redis_host', 'localhost');
    } else {
        set('cache_driver', 'file');
    }
    
    // Dynamic asset configuration
    if ($stage === 'production') {
        set('asset_url', 'https://cdn.myapp.com');
        set('asset_compression', true);
    } else {
        set('asset_url', 'http://localhost:3000');
        set('asset_compression', false);
    }
}, [
    'description' => 'Configure dynamic settings',
]);

// Add to deployment flow
klytron_add_hook('before:deploy:update_code', 'deploy:configure_dynamic');
```

### Configuration Validation

Validate configuration before deployment:

```php
// Configuration validation
klytron_add_task('deploy:validate_config', function () {
    writeln('<info>Validating configuration...</info>');
    
    // Required settings
    $required = ['app_name', 'repository', 'deploy_path'];
    
    foreach ($required as $setting) {
        if (!get($setting)) {
            throw new Exception("Required setting '{$setting}' is not configured");
        }
    }
    
    // Validate paths
    $deployPath = get('deploy_path');
    if (!is_dir($deployPath)) {
        throw new Exception("Deploy path '{$deployPath}' does not exist");
    }
    
    // Validate database settings
    if (get('database') !== 'none') {
        $dbSettings = ['db_host', 'db_name', 'db_user'];
        foreach ($dbSettings as $setting) {
            if (!get($setting)) {
                throw new Exception("Database setting '{$setting}' is required");
            }
        }
    }
    
    writeln('<info>Configuration validation passed!</info>');
}, [
    'description' => 'Validate deployment configuration',
]);

// Add to deployment flow
klytron_add_hook('before:deploy', 'deploy:validate_config');
```

## 🎯 Custom Notifications

### Deployment Notifications

Add custom notification systems:

```php
// Slack notification
klytron_add_task('deploy:notify_slack', function () {
    $webhookUrl = get('slack_webhook_url');
    $channel = get('slack_channel', '#deployments');
    $stage = get('stage', 'production');
    $appName = get('app_name');
    
    if ($webhookUrl) {
        $message = [
            'channel' => $channel,
            'text' => "🚀 Deployment to {$stage} completed successfully for {$appName}",
            'attachments' => [
                [
                    'color' => 'good',
                    'fields' => [
                        [
                            'title' => 'Application',
                            'value' => $appName,
                            'short' => true
                        ],
                        [
                            'title' => 'Environment',
                            'value' => $stage,
                            'short' => true
                        ],
                        [
                            'title' => 'Timestamp',
                            'value' => date('Y-m-d H:i:s'),
                            'short' => true
                        ]
                    ]
                ]
            ]
        ];
        
        $json = json_encode($message);
        run("curl -X POST -H 'Content-type: application/json' --data '{$json}' {$webhookUrl}");
    }
}, [
    'description' => 'Send Slack notification',
]);

// Email notification
klytron_add_task('deploy:notify_email', function () {
    $emailRecipients = get('email_recipients', []);
    $appName = get('app_name');
    $stage = get('stage', 'production');
    
    if (!empty($emailRecipients)) {
        $subject = "Deployment completed: {$appName} to {$stage}";
        $body = "Deployment to {$stage} completed successfully for {$appName} at " . date('Y-m-d H:i:s');
        
        foreach ($emailRecipients as $email) {
            run("echo '{$body}' | mail -s '{$subject}' {$email}");
        }
    }
}, [
    'description' => 'Send email notification',
]);

// Add notifications to deployment flow
klytron_add_hook('after:deploy', 'deploy:notify_slack');
klytron_add_hook('after:deploy', 'deploy:notify_email');
```

## 🎯 Custom Health Checks

### Application Health Checks

Create custom health check tasks:

```php
// Health check task
klytron_add_task('deploy:health_check', function () {
    writeln('<info>Running health checks...</info>');
    
    $healthChecks = [
        'Application accessible' => 'curl -f http://localhost/health || exit 1',
        'Database connection' => 'php artisan db:monitor',
        'Cache working' => 'php artisan cache:test',
        'Queue working' => 'php artisan queue:monitor',
    ];
    
    foreach ($healthChecks as $check => $command) {
        try {
            writeln("<info>Checking: {$check}</info>");
            run($command);
            writeln("<info>✓ {$check} passed</info>");
        } catch (Exception $e) {
            writeln("<error>✗ {$check} failed: " . $e->getMessage() . "</error>");
            throw $e;
        }
    }
    
    writeln('<info>All health checks passed!</info>');
}, [
    'description' => 'Run application health checks',
]);

// Add health check to deployment flow
klytron_add_hook('after:deploy:symlink', 'deploy:health_check');
```

## 🎯 Custom Rollback

### Advanced Rollback Strategy

Create custom rollback procedures:

```php
// Custom rollback task
klytron_add_task('deploy:custom_rollback', function () {
    writeln('<info>Starting custom rollback...</info>');
    
    // Get current and previous releases
    $currentRelease = get('current_release');
    $previousRelease = get('previous_release');
    
    if (!$previousRelease) {
        throw new Exception('No previous release available for rollback');
    }
    
    // Database rollback
    if (get('database') !== 'none') {
        writeln('<info>Rolling back database...</info>');
        invoke('deploy:database:rollback');
    }
    
    // Switch to previous release
    writeln('<info>Switching to previous release...</info>');
    run("ln -sfn {$previousRelease} {$currentRelease}");
    
    // Clear caches
    writeln('<info>Clearing caches...</info>');
    run('php artisan cache:clear');
    run('php artisan config:clear');
    
    // Health check
    writeln('<info>Running health check after rollback...</info>');
    invoke('deploy:health_check');
    
    writeln('<info>Rollback completed successfully!</info>');
}, [
    'description' => 'Custom rollback procedure',
]);
```

## 🎯 Best Practices for Customization

### Code Organization

1. **Separate Concerns**: Keep different types of customizations separate
2. **Use Descriptive Names**: Make task and function names clear
3. **Add Documentation**: Document complex customizations
4. **Version Control**: Keep customizations in version control
5. **Test Thoroughly**: Test customizations before production use

### Performance Considerations

1. **Minimize I/O**: Reduce file system operations
2. **Use Caching**: Cache expensive operations
3. **Parallel Execution**: Run independent tasks in parallel
4. **Optimize Commands**: Use efficient shell commands
5. **Monitor Performance**: Track execution times

### Security Considerations

1. **Validate Inputs**: Validate all user inputs
2. **Use Secure Commands**: Avoid shell injection
3. **Limit Permissions**: Use minimum required permissions
4. **Log Security Events**: Log security-related operations
5. **Audit Custom Code**: Regularly audit custom code

## 🎯 Example Custom Deployment

Here's a complete example of a customized deployment:

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Basic configuration
klytron_configure_app('my-custom-app', 'git@github.com:user/my-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('myapp.com');

// Custom project configuration
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    'supports_vite' => true,
    'supports_storage_link' => true,
    'custom_notifications' => true,
    'health_checks' => true,
]);

// Custom host configuration
klytron_configure_host('myapp.com', [
    'remote_user' => 'deploy',
    'http_user' => 'www-data',
    'slack_webhook_url' => 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL',
    'email_recipients' => ['admin@myapp.com'],
]);

// Custom tasks
klytron_add_task('deploy:custom_prep', function () {
    writeln('<info>Running custom preparation...</info>');
    run('php artisan down --message="Deploying..." --retry=60');
}, ['description' => 'Custom preparation']);

klytron_add_task('deploy:custom_post', function () {
    writeln('<info>Running custom post-deployment...</info>');
    run('php artisan up');
    run('php artisan queue:restart');
}, ['description' => 'Custom post-deployment']);

klytron_add_task('deploy:notify', function () {
    invoke('deploy:notify_slack');
    invoke('deploy:notify_email');
}, ['description' => 'Send notifications']);

// Add custom tasks to deployment flow
klytron_add_hook('before:deploy', 'deploy:custom_prep');
klytron_add_hook('after:deploy', 'deploy:custom_post');
klytron_add_hook('after:deploy', 'deploy:notify');
klytron_add_hook('after:deploy:symlink', 'deploy:health_check');
```

## 🎯 Next Steps

- **Read the [Configuration Reference](configuration-reference.md)** - Complete configuration options
- **Explore [Examples](examples/)** - Real-world customization examples
- **Check [Best Practices](best-practices.md)** - Customization best practices
- **Review [Task Reference](task-reference.md)** - Available tasks and hooks
