# 🔌 API Reference

[← Back to Documentation](README.md)

## 📋 Table of Contents

- [Overview](#overview)
- [Core Classes](#core-classes)
- [Configuration API](#configuration-api)
- [Deployment API](#deployment-api)
- [Task API](#task-api)
- [Event System](#event-system)
- [Integration Examples](#integration-examples)
- [Error Handling](#error-handling)

## 🌟 Overview

This document provides a comprehensive API reference for developers who want to integrate with Klytron Deployer programmatically. The API allows you to control deployments, manage configurations, and automate deployment processes.

### 🔧 Key Features

- **Programmatic Control**: Full control over deployment processes
- **Configuration Management**: Dynamic configuration updates
- **Event Handling**: Hook into deployment events
- **Error Handling**: Comprehensive error management
- **Integration Ready**: Easy integration with CI/CD systems

## 🏗️ Core Classes

### KlytronDeployer

The main class for programmatic deployment control.

```php
class KlytronDeployer
{
    public function __construct(array $config = []);
    public function configure(array $config): self;
    public function deploy(string $environment = 'production'): bool;
    public function rollback(): bool;
    public function status(): array;
    public function validate(): bool;
}
```

### ConfigurationManager

Manages deployment configurations programmatically.

```php
class ConfigurationManager
{
    public function set(string $key, $value): self;
    public function get(string $key, $default = null);
    public function has(string $key): bool;
    public function remove(string $key): self;
    public function all(): array;
    public function merge(array $config): self;
}
```

### DeploymentManager

Handles deployment operations and workflows.

```php
class DeploymentManager
{
    public function start(): bool;
    public function stop(): bool;
    public function pause(): bool;
    public function resume(): bool;
    public function getStatus(): string;
    public function getLogs(): array;
}
```

## ⚙️ Configuration API

### Basic Configuration

```php
// Initialize with configuration
$deployer = new KlytronDeployer([
    'application' => 'my-app',
    'repository' => 'git@github.com:user/my-app.git',
    'deploy_path' => '/var/www',
    'hosts' => [
        'production' => [
            'hostname' => 'server.com',
            'remote_user' => 'deploy',
            'http_user' => 'www-data',
        ]
    ]
]);

// Or configure after initialization
$deployer->configure([
    'keep_releases' => 5,
    'default_timeout' => 1800,
    'shared_files' => ['.env'],
    'shared_dirs' => ['storage', 'logs'],
]);
```

### Advanced Configuration

```php
// Framework-specific configuration
$deployer->configure([
    'project' => [
        'type' => 'laravel',
        'database' => 'mysql',
        'supports_vite' => true,
        'supports_passport' => false,
    ],
    'database' => [
        'host' => 'localhost',
        'database' => 'myapp',
        'username' => 'dbuser',
        'password' => 'dbpass',
    ],
    'backup' => [
        'enabled' => true,
        'compress' => true,
        'retention' => [
            'days' => 30,
            'max_backups' => 50,
        ],
    ],
]);
```

## 🚀 Deployment API

### Basic Deployment

```php
// Simple deployment
$deployer = new KlytronDeployer($config);
$success = $deployer->deploy('production');

if ($success) {
    echo "Deployment successful!";
} else {
    echo "Deployment failed!";
}
```

### Advanced Deployment Control

```php
// Deployment with custom options
$options = [
    'branch' => 'feature/new-feature',
    'skip_tests' => false,
    'clear_cache' => true,
    'run_migrations' => true,
    'backup_before' => true,
];

$success = $deployer->deploy('staging', $options);
```

### Deployment Status and Control

```php
// Check deployment status
$status = $deployer->status();
echo "Current status: " . $status['state'];
echo "Last deployment: " . $status['last_deployment'];
echo "Active releases: " . $status['active_releases'];

// Control deployment
$deployer->pause();   // Pause current deployment
$deployer->resume();  // Resume paused deployment
$deployer->stop();    // Stop current deployment
```

### Rollback Operations

```php
// Rollback to previous release
$success = $deployer->rollback();

// Rollback to specific release
$success = $deployer->rollback('2024-01-15-10-30-00');

// Rollback with options
$success = $deployer->rollback(null, [
    'backup_current' => true,
    'skip_tests' => true,
]);
```

## 📋 Task API

### Custom Task Execution

```php
// Execute specific tasks
$deployer->executeTask('deploy:update_code');
$deployer->executeTask('deploy:shared');
$deployer->executeTask('deploy:symlink');

// Execute multiple tasks
$deployer->executeTasks([
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
]);
```

### Task Information

```php
// Get available tasks
$tasks = $deployer->getAvailableTasks();
foreach ($tasks as $task) {
    echo "Task: " . $task['name'];
    echo "Description: " . $task['description'];
    echo "Dependencies: " . implode(', ', $task['dependencies']);
}

// Get task details
$taskInfo = $deployer->getTaskInfo('deploy:update_code');
echo "Task: " . $taskInfo['name'];
echo "Description: " . $taskInfo['description'];
echo "Required: " . ($taskInfo['required'] ? 'Yes' : 'No');
```

## 🔄 Event System

### Event Listeners

```php
// Register event listeners
$deployer->on('deploy:start', function($event) {
    echo "Deployment started at: " . $event['timestamp'];
});

$deployer->on('deploy:success', function($event) {
    echo "Deployment completed successfully!";
    echo "Duration: " . $event['duration'] . " seconds";
});

$deployer->on('deploy:error', function($event) {
    echo "Deployment failed: " . $event['error'];
    // Send notification, log error, etc.
});

$deployer->on('task:start', function($event) {
    echo "Starting task: " . $event['task'];
});

$deployer->on('task:complete', function($event) {
    echo "Completed task: " . $event['task'];
    echo "Duration: " . $event['duration'] . " seconds";
});
```

### Available Events

| Event | Description | Data |
|-------|-------------|------|
| `deploy:start` | Deployment started | `timestamp`, `environment` |
| `deploy:success` | Deployment completed successfully | `timestamp`, `duration`, `environment` |
| `deploy:error` | Deployment failed | `timestamp`, `error`, `environment` |
| `deploy:rollback` | Rollback started | `timestamp`, `release` |
| `task:start` | Task started | `task`, `timestamp` |
| `task:complete` | Task completed | `task`, `timestamp`, `duration` |
| `task:error` | Task failed | `task`, `timestamp`, `error` |
| `backup:start` | Backup started | `timestamp`, `type` |
| `backup:complete` | Backup completed | `timestamp`, `duration`, `size` |

## 🔗 Integration Examples

### CI/CD Integration

```php
// GitHub Actions integration
class GitHubActionsDeployer
{
    private $deployer;
    
    public function __construct()
    {
        $this->deployer = new KlytronDeployer([
            'application' => $_ENV['APP_NAME'],
            'repository' => $_ENV['GITHUB_REPOSITORY'],
            'deploy_path' => $_ENV['DEPLOY_PATH'],
            'hosts' => [
                'production' => [
                    'hostname' => $_ENV['SERVER_HOST'],
                    'remote_user' => $_ENV['DEPLOY_USER'],
                    'http_user' => $_ENV['HTTP_USER'],
                ]
            ]
        ]);
    }
    
    public function deploy()
    {
        try {
            $this->deployer->on('deploy:success', function($event) {
                // Update deployment status
                $this->updateGitHubStatus('success', 'Deployment completed');
            });
            
            $this->deployer->on('deploy:error', function($event) {
                // Update deployment status
                $this->updateGitHubStatus('failure', $event['error']);
            });
            
            return $this->deployer->deploy('production');
        } catch (Exception $e) {
            error_log("Deployment failed: " . $e->getMessage());
            return false;
        }
    }
}
```

### Webhook Integration

```php
// Webhook handler for automatic deployments
class WebhookHandler
{
    private $deployer;
    
    public function __construct()
    {
        $this->deployer = new KlytronDeployer();
    }
    
    public function handlePush($payload)
    {
        // Verify webhook signature
        if (!$this->verifySignature($payload)) {
            throw new Exception('Invalid webhook signature');
        }
        
        // Check if deployment should be triggered
        if ($this->shouldDeploy($payload)) {
            $this->deployer->deploy('production');
        }
    }
    
    private function shouldDeploy($payload)
    {
        return $payload['ref'] === 'refs/heads/main' && 
               $payload['repository']['name'] === 'my-app';
    }
}
```

### Monitoring Integration

```php
// Monitoring system integration
class MonitoringDeployer
{
    private $deployer;
    private $monitor;
    
    public function __construct()
    {
        $this->deployer = new KlytronDeployer();
        $this->monitor = new MonitoringService();
    }
    
    public function deployWithMonitoring()
    {
        $this->deployer->on('deploy:start', function($event) {
            $this->monitor->startDeployment($event['environment']);
        });
        
        $this->deployer->on('deploy:success', function($event) {
            $this->monitor->completeDeployment($event['environment'], $event['duration']);
        });
        
        $this->deployer->on('deploy:error', function($event) {
            $this->monitor->failDeployment($event['environment'], $event['error']);
        });
        
        return $this->deployer->deploy('production');
    }
}
```

## ⚠️ Error Handling

### Exception Types

```php
// KlytronDeployerException - Base exception
try {
    $deployer->deploy('production');
} catch (KlytronDeployerException $e) {
    echo "Deployment error: " . $e->getMessage();
    echo "Error code: " . $e->getCode();
}

// ConfigurationException - Configuration errors
try {
    $deployer->configure($invalidConfig);
} catch (ConfigurationException $e) {
    echo "Configuration error: " . $e->getMessage();
    echo "Invalid keys: " . implode(', ', $e->getInvalidKeys());
}

// DeploymentException - Deployment-specific errors
try {
    $deployer->deploy('production');
} catch (DeploymentException $e) {
    echo "Deployment failed: " . $e->getMessage();
    echo "Failed task: " . $e->getFailedTask();
    echo "Error details: " . $e->getDetails();
}

// ConnectionException - SSH/connection errors
try {
    $deployer->deploy('production');
} catch (ConnectionException $e) {
    echo "Connection failed: " . $e->getMessage();
    echo "Host: " . $e->getHost();
    echo "User: " . $e->getUser();
}
```

### Error Recovery

```php
// Automatic retry with exponential backoff
class ResilientDeployer
{
    private $deployer;
    private $maxRetries = 3;
    
    public function deployWithRetry($environment)
    {
        $attempt = 1;
        
        while ($attempt <= $this->maxRetries) {
            try {
                return $this->deployer->deploy($environment);
            } catch (DeploymentException $e) {
                if ($attempt === $this->maxRetries) {
                    throw $e;
                }
                
                $delay = pow(2, $attempt) * 60; // Exponential backoff
                sleep($delay);
                $attempt++;
            }
        }
    }
}
```

### Logging and Debugging

```php
// Enable detailed logging
$deployer->setLogLevel('debug');

// Get deployment logs
$logs = $deployer->getLogs();
foreach ($logs as $log) {
    echo "[" . $log['timestamp'] . "] " . $log['level'] . ": " . $log['message'];
}

// Custom logging
$deployer->on('deploy:start', function($event) {
    error_log("Deployment started: " . json_encode($event));
});

$deployer->on('deploy:error', function($event) {
    error_log("Deployment error: " . json_encode($event));
    // Send to external logging service
    $this->sendToLoggingService($event);
});
```

## 📚 Additional Resources

- **[Function Reference](function-reference.md)** - Complete function documentation
- **[Configuration Reference](configuration-reference.md)** - All configuration options
- **[Troubleshooting](troubleshooting.md)** - Common API issues and solutions
- **[Examples](../examples/)** - Real-world integration examples

---

**🔍 Need Help?**: Check the [Troubleshooting Guide](troubleshooting.md) for API-related issues.
