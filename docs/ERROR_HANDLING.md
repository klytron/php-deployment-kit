# Error Handling Guide

## Overview

The Klytron PHP Deployment Kit includes comprehensive error handling to provide clear feedback and help debug deployment issues.

## Error Handling Components

### 1. DeploymentErrorHandler

The main error handling service that:
- Collects errors and warnings
- Generates detailed reports
- Logs errors to files
- Provides context for debugging

### 2. DeploymentValidator

Validates deployment configuration before deployment:
- Checks required fields
- Validates paths and permissions
- Tests SSH connectivity
- Verifies binary availability

### 3. EnhancedDeploymentTask

Provides pre and post-deployment health checks:
- Application URL accessibility
- SSL certificate validation
- Database connection testing
- File permission verification

## Using Error Handling

### Basic Error Handling
```php
use Klytron\PhpDeploymentKit\Services\DeploymentErrorHandler;

$errorHandler = new DeploymentErrorHandler();

// Add error with context
$errorHandler->handleError('Database connection failed', [
    'host' => 'localhost',
    'database' => 'myapp',
    'port' => 3306
]);

// Add warning
$errorHandler->addWarning('Using default configuration', [
    'config_file' => '.env'
]);

// Generate report
$report = $errorHandler->generateReport();
echo $report;
```

### Exception Handling
```php
try {
    // Deployment code
} catch (Exception $e) {
    $errorHandler->handleException($e, [
        'deployment_step' => 'database_migration',
        'release' => 'v1.2.3'
    ]);
}
```

### Validation
```php
use Klytron\PhpDeploymentKit\Services\DeploymentValidationService;

$validator = new DeploymentValidationService();
$config = [
    'app_name' => 'MyApp',
    'repository' => 'git@github.com:user/myapp.git',
    'deploy_path' => '/var/www/myapp',
    'domain' => 'example.com'
];

$results = $validator->validateDeployment($config);

if (!$results['overall_valid']) {
    $report = $validator->generateReport($results);
    throw new RuntimeException("Validation failed:\n" . $report);
}
```

## Error Types

### Configuration Errors
- Missing required fields
- Invalid paths
- Incorrect server configuration
- Invalid project type or database

### Connectivity Errors
- SSH connection failures
- Repository access issues
- Database connection problems
- Network timeouts

### Runtime Errors
- Permission denied
- File not found
- Command execution failures
- Resource limits

### Health Check Failures
- Application not accessible
- SSL certificate issues
- Broken storage links
- Incorrect file permissions

## Error Reports

### Standard Error Report Format
```
=== Deployment Error Report ===
Generated: 2024-01-15 10:30:45

ERRORS (2):
==================================================
[1] 2024-01-15 10:30:45 - Database connection failed
    File: /path/to/file.php:123
    Exception: PDOException
    Context: {
        "host": "localhost",
        "database": "myapp"
    }

[2] 2024-01-15 10:31:02 - Permission denied
    File: /path/to/another.php:456
    Context: {
        "path": "/var/www/myapp/storage",
        "user": "www-data"
    }

WARNINGS (1):
--------------------------------------------------
[1] 2024-01-15 10:32:15 - Using default configuration
    Context: {
        "config_file": ".env"
    }
```

### Validation Report Format
```
=== Deployment Validation Report ===

Configuration: ✅ VALID

SSH Connectivity: ❌ SOME FAILED
  ❌ production-server
    Error: SSH connection failed

Required Binaries: ✅ ALL AVAILABLE
  ✅ git (/usr/bin/git)
  ✅ composer (/usr/local/bin/composer)

Overall Status: ❌ FIX ISSUES BEFORE DEPLOYMENT
```

## Logging

### Error Log Location
- Default: `/tmp/deployment_errors.log`
- Custom: `DeploymentErrorHandler::setLogFile('/custom/path.log')`

### Log Format
```json
{"timestamp":"2024-01-15 10:30:45","severity":"error","message":"Database connection failed","context":{"host":"localhost"},"stack_trace":"..."}
```

### Log Rotation
- Logs are appended, not rotated
- Implement log rotation in your deployment script if needed
- Consider logrotate for production environments

## Debugging Tips

### 1. Enable Verbose Output
```bash
vendor/bin/dep deploy -vvv
```

### 2. Check Error Logs
```bash
tail -f /tmp/deployment_errors.log
```

### 3. Run Validation First
```bash
vendor/bin/dep klytron:validate
```

### 4. Test Connectivity
```bash
ssh user@server "echo 'connection test'"
```

### 5. Check Permissions
```bash
ls -la /var/www/myapp/storage
```

## Best Practices

### 1. Validate Before Deployment
Always run validation before starting deployment:
```php
$validator = new DeploymentValidationService();
$results = $validator->validateDeployment($config);

if (!$results['overall_valid']) {
    throw new RuntimeException("Validation failed");
}
```

### 2. Handle Exceptions Gracefully
```php
try {
    deploy();
} catch (Exception $e) {
    $errorHandler->handleException($e);
    rollback();
    throw $e;
}
```

### 3. Provide Context
Always include relevant context when logging errors:
```php
$errorHandler->handleError('Step failed', [
    'step' => 'migration',
    'release' => $releaseId,
    'timestamp' => time()
]);
```

### 4. Use Appropriate Severity Levels
- **Error**: Deployment-blocking issues
- **Warning**: Non-critical issues
- **Info**: Informational messages

### 5. Implement Rollback
Always have a rollback strategy when errors occur:
```php
try {
    deploy();
} catch (Exception $e) {
    rollback();
    notifyError($e);
    throw $e;
}
```

## Integration with Monitoring

### Slack Notifications
```php
if ($errorHandler->hasErrors()) {
    $report = $errorHandler->generateReport();
    file_get_contents('https://hooks.slack.com/...', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/json',
            'content' => json_encode(['text' => $report])
        ]
    ]));
}
```

### Email Notifications
```php
if ($errorHandler->hasErrors()) {
    $report = $errorHandler->generateReport();
    mail('admin@example.com', 'Deployment Failed', $report);
}
```

## Troubleshooting Common Issues

### "No SSH key found"
- Check SSH key paths in configuration
- Verify SSH agent is running
- Test manual SSH connection

### "Permission denied"
- Check file permissions on target server
- Verify user has necessary rights
- Check sudo requirements

### "Database connection failed"
- Verify database credentials
- Check database server status
- Test connection manually

### "Application not accessible"
- Check web server configuration
- Verify DNS settings
- Test URL manually

For more help, see the [Troubleshooting Guide](troubleshooting.md).
