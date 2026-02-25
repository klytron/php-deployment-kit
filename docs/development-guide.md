# 🛠️ Development Guide

[← Back to Documentation](README.md)

## 📋 Table of Contents

- [Overview](#overview)
- [Development Setup](#development-setup)
- [Project Structure](#project-structure)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Documentation](#documentation)
- [Contributing Workflow](#contributing-workflow)
- [Release Process](#release-process)
- [Architecture](#architecture)

## 🌟 Overview

This guide is for developers who want to contribute to Klytron Deployer or understand its internal architecture. It covers development setup, coding standards, testing procedures, and the contribution workflow.

### 🎯 Target Audience

- **Contributors** - Developers who want to add features or fix bugs
- **Maintainers** - Core team members who review and merge contributions
- **Integrators** - Developers who want to understand the architecture for custom integrations

## 🚀 Development Setup

### Prerequisites

- **PHP**: 8.1 or higher
- **Composer**: Latest version
- **Git**: For version control
- **SSH**: For testing deployments
- **Docker** (optional): For isolated testing environments

### Local Development Setup

```bash
# Clone the repository
git clone https://github.com/klytron/php-deployment-kit.git
cd deployment-kit

# Install dependencies
composer install

# Install development dependencies
composer install --dev

# Set up pre-commit hooks (optional)
cp .git/hooks/pre-commit.sample .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

### IDE Configuration

#### VS Code

Create `.vscode/settings.json`:

```json
{
    "php.validate.executablePath": "/usr/bin/php",
    "php.suggest.basic": false,
    "phpcs.standard": "PSR12",
    "phpcs.executablePath": "./vendor/bin/phpcs",
    "phpstan.enabled": true,
    "phpstan.executablePath": "./vendor/bin/phpstan"
}
```

#### PHPStorm

1. **Configure PHP Interpreter**: Set to PHP 8.1+
2. **Enable Code Style**: Use PSR-12 standard
3. **Configure Quality Tools**: Set up PHPStan and PHPCS
4. **Enable Git Integration**: Configure Git hooks

### Environment Variables

Create `.env.local` for development:

```env
# Development settings
APP_ENV=local
APP_DEBUG=true

# Test server settings
TEST_SERVER_HOST=localhost
TEST_SERVER_USER=deploy
TEST_SERVER_PATH=/tmp/test-deploy

# Database settings (for testing)
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

## 🏗️ Project Structure

### Core Files

```
deployment-kit/
├── 📁 src/                    # Source code
│   ├── Core/                  # Core functionality
│   ├── Tasks/                 # Task definitions
│   ├── Recipes/               # Framework-specific recipes
│   └── Utils/                 # Utility functions
├── 📁 tests/                  # Test files
│   ├── Unit/                  # Unit tests
│   ├── Integration/           # Integration tests
│   └── Fixtures/              # Test data
├── 📁 docs/                   # Documentation
├── 📁 examples/               # Usage examples
├── 📁 templates/              # Deployment templates
├── 📁 tools/                  # Development tools
└── 📁 working-deploy-scripts/ # Real-world examples
```

### Key Components

#### Core Library (`src/Core/`)

- **DeploymentManager.php** - Main deployment orchestration
- **ConfigurationManager.php** - Configuration management
- **TaskManager.php** - Task execution and management
- **ValidationManager.php** - Input validation and verification

#### Task Definitions (`src/Tasks/`)

- **DeploymentTasks.php** - Core deployment tasks
- **DatabaseTasks.php** - Database-related tasks
- **BackupTasks.php** - Backup and restore tasks
- **UtilityTasks.php** - Helper and utility tasks

#### Recipes (`src/Recipes/`)

- **LaravelRecipe.php** - Laravel-specific tasks
- **Yii2Recipe.php** - Yii2-specific tasks
- **SimplePhpRecipe.php** - Simple PHP tasks

## 📝 Coding Standards

### PHP Standards

#### PSR-12 Compliance

```php
<?php

declare(strict_types=1);

namespace Klytron\Deployer\Core;

use Klytron\Deployer\Exceptions\ConfigurationException;
use Klytron\Deployer\Interfaces\TaskInterface;

/**
 * Main deployment manager class.
 *
 * @package Klytron\Deployer\Core
 * @author Michael K. Laweh (klytron)
 */
class DeploymentManager implements TaskInterface
{
    private ConfigurationManager $config;
    private array $tasks = [];

    public function __construct(ConfigurationManager $config)
    {
        $this->config = $config;
    }

    /**
     * Execute deployment process.
     *
     * @param string $environment Environment name
     * @param array $options Deployment options
     * @return bool Success status
     * @throws ConfigurationException If configuration is invalid
     */
    public function deploy(string $environment, array $options = []): bool
    {
        // Implementation
    }
}
```

#### Function Naming

- **Functions**: `snake_case` (e.g., `klytron_configure_app`)
- **Methods**: `camelCase` (e.g., `configureApp`)
- **Classes**: `PascalCase` (e.g., `DeploymentManager`)
- **Constants**: `UPPER_SNAKE_CASE` (e.g., `DEFAULT_TIMEOUT`)

#### Documentation Standards

```php
/**
 * Configure application settings.
 *
 * @param string $name Application name
 * @param string $repository Repository URL
 * @param array $config Additional configuration
 * @return void
 * @throws ConfigurationException If configuration is invalid
 * @example
 * klytron_configure_app('my-app', 'git@github.com:user/my-app.git', [
 *     'keep_releases' => 5,
 *     'default_timeout' => 1800,
 * ]);
 */
function klytron_configure_app(string $name, string $repository, array $config = []): void
{
    // Implementation
}
```

### Code Quality Tools

#### PHPStan Configuration

Create `phpstan.neon`:

```neon
parameters:
    level: 8
    paths:
        - src/
        - tests/
    excludePaths:
        - tests/Fixtures/
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
```

#### PHPCS Configuration

Create `phpcs.xml`:

```xml
<?xml version="1.0"?>
<ruleset name="Klytron Deployer">
    <description>PSR-12 coding standards for Klytron Deployer</description>
    
    <file>src/</file>
    <file>tests/</file>
    
    <rule ref="PSR12"/>
    <rule ref="Generic.Files.LineLength">
        <properties>
            <property name="lineLimit" value="120"/>
            <property name="absoluteLineLimit" value="150"/>
        </properties>
    </rule>
    
    <exclude-pattern>tests/Fixtures/</exclude-pattern>
</ruleset>
```

## 🧪 Testing

### Running Tests

```bash
# Run all tests
composer test

# Run unit tests only
composer test:unit

# Run integration tests only
composer test:integration

# Run with coverage
composer test:coverage

# Run specific test file
./vendor/bin/phpunit tests/Unit/DeploymentManagerTest.php
```

### Test Structure

#### Unit Tests

```php
<?php

declare(strict_types=1);

namespace Klytron\Deployer\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Klytron\Deployer\Core\DeploymentManager;
use Klytron\Deployer\Core\ConfigurationManager;

class DeploymentManagerTest extends TestCase
{
    private DeploymentManager $manager;
    private ConfigurationManager $config;

    protected function setUp(): void
    {
        $this->config = new ConfigurationManager();
        $this->manager = new DeploymentManager($this->config);
    }

    public function testDeployWithValidConfiguration(): void
    {
        // Arrange
        $this->config->set('application', 'test-app');
        $this->config->set('repository', 'git@github.com:test/repo.git');

        // Act
        $result = $this->manager->deploy('production');

        // Assert
        $this->assertTrue($result);
    }

    public function testDeployWithInvalidConfiguration(): void
    {
        // Arrange
        $this->config->set('application', ''); // Invalid

        // Act & Assert
        $this->expectException(ConfigurationException::class);
        $this->manager->deploy('production');
    }
}
```

#### Integration Tests

```php
<?php

declare(strict_types=1);

namespace Klytron\Deployer\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Klytron\Deployer\KlytronDeployer;

class LaravelDeploymentTest extends TestCase
{
    private KlytronDeployer $deployer;

    protected function setUp(): void
    {
        $this->deployer = new KlytronDeployer([
            'application' => 'test-laravel',
            'repository' => 'git@github.com:test/laravel-app.git',
            'deploy_path' => '/tmp/test-deploy',
        ]);
    }

    public function testLaravelDeploymentWorkflow(): void
    {
        // Test complete Laravel deployment workflow
        $result = $this->deployer->deploy('test');
        
        $this->assertTrue($result);
        $this->assertDirectoryExists('/tmp/test-deploy/current');
        $this->assertFileExists('/tmp/test-deploy/current/.env');
    }
}
```

### Test Quality Tools

#### PHPUnit Configuration

Create `phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    
    <coverage>
        <include>
            <directory suffix=".php">src/</directory>
        </include>
        <exclude>
            <directory>tests/</directory>
        </exclude>
    </coverage>
</phpunit>
```

## 📚 Documentation

### Documentation Standards

#### Adding Documentation

1. **Update existing docs** when changing functionality
2. **Add examples** for new features
3. **Include troubleshooting** for complex features
4. **Cross-reference** related documentation

#### Documentation Structure

```markdown
# Feature Name

[← Back to Documentation](README.md)

## Overview

Brief description of the feature.

## Usage

```php
// Code example
klytron_configure_feature('value');
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `option` | string | `default` | Description |

## Examples

### Basic Usage

```php
// Basic example
```

### Advanced Usage

```php
// Advanced example
```

## Troubleshooting

Common issues and solutions.

## Related

- [Related Documentation](related.md)
```

### Testing Documentation

```bash
# Test documentation links
composer docs:test-links

# Validate markdown
composer docs:validate

# Generate documentation coverage report
composer docs:coverage
```

## 🔄 Contributing Workflow

### Branch Strategy

- **main**: Production-ready code
- **develop**: Integration branch for features
- **feature/***: Individual features
- **bugfix/***: Bug fixes
- **hotfix/***: Critical fixes

### Development Process

1. **Create feature branch**:
   ```bash
   git checkout -b feature/new-feature
   ```

2. **Make changes**:
   ```bash
   # Make your changes
   git add .
   git commit -m "feat: add new deployment feature"
   ```

3. **Run tests**:
   ```bash
   composer test
   composer test:coverage
   ```

4. **Update documentation**:
   ```bash
   # Update relevant documentation
   composer docs:validate
   ```

5. **Create pull request**:
   - Fill out PR template
   - Link related issues
   - Request reviews

### Commit Message Format

```
type(scope): description

[optional body]

[optional footer]
```

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Test changes
- `chore`: Maintenance tasks

**Examples**:
```
feat(core): add database backup functionality
fix(laravel): resolve Vite build issues
docs(api): update API reference documentation
test(integration): add deployment workflow tests
```

### Pull Request Process

1. **Fork the repository**
2. **Create feature branch**
3. **Make changes with tests**
4. **Update documentation**
5. **Run quality checks**:
   ```bash
   composer test
   composer analyse
   composer docs:validate
   ```
6. **Submit pull request**
7. **Address review feedback**
8. **Merge when approved**

## 🚀 Release Process

### Version Management

- **Semantic Versioning**: MAJOR.MINOR.PATCH
- **Changelog**: Update CHANGELOG.md for each release
- **Git Tags**: Tag releases in Git

### Release Checklist

- [ ] **Update version** in composer.json
- [ ] **Update changelog** with new features/fixes
- [ ] **Run full test suite**:
  ```bash
  composer test
  composer test:coverage
  composer analyse
  ```
- [ ] **Update documentation** if needed
- [ ] **Create release branch**:
  ```bash
  git checkout -b release/v1.2.0
  ```
- [ ] **Tag release**:
  ```bash
  git tag -a v1.2.0 -m "Release v1.2.0"
  git push origin v1.2.0
  ```
- [ ] **Merge to main**:
  ```bash
  git checkout main
  git merge release/v1.2.0
  git push origin main
  ```
- [ ] **Create GitHub release** with changelog
- [ ] **Update documentation** links if needed

### Automated Release

```yaml
# .github/workflows/release.yml
name: Release

on:
  push:
    tags:
      - 'v*'

jobs:
  release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run tests
        run: composer test
      - name: Create release
        uses: actions/create-release@v1
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

## 🏗️ Architecture

### Design Patterns

#### Factory Pattern

```php
class TaskFactory
{
    public static function create(string $type, array $config): TaskInterface
    {
        return match ($type) {
            'deployment' => new DeploymentTask($config),
            'database' => new DatabaseTask($config),
            'backup' => new BackupTask($config),
            default => throw new InvalidArgumentException("Unknown task type: $type"),
        };
    }
}
```

#### Strategy Pattern

```php
interface DeploymentStrategy
{
    public function deploy(array $config): bool;
}

class LaravelStrategy implements DeploymentStrategy
{
    public function deploy(array $config): bool
    {
        // Laravel-specific deployment logic
    }
}

class Yii2Strategy implements DeploymentStrategy
{
    public function deploy(array $config): bool
    {
        // Yii2-specific deployment logic
    }
}
```

#### Observer Pattern

```php
class DeploymentEventManager
{
    private array $listeners = [];

    public function on(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function trigger(string $event, array $data = []): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener($data);
        }
    }
}
```

### Extension Points

#### Custom Tasks

```php
// Register custom task
task('custom:setup', function () {
    info('Running custom setup...');
    // Custom logic
})->desc('Custom setup task');

// Use in deployment flow
task('deploy', [
    'deploy:prepare',
    'custom:setup',
    'deploy:release',
    'deploy:cleanup',
]);
```

#### Custom Recipes

```php
// Create custom recipe
class CustomRecipe
{
    public static function load(): void
    {
        // Register custom tasks
        task('custom:deploy', function () {
            // Custom deployment logic
        });
    }
}

// Use in deployment script
require 'vendor/klytron/php-deployment-kit/recipes/custom-recipe.php';
```

### Performance Considerations

#### Caching

```php
class CacheManager
{
    private array $cache = [];

    public function get(string $key, callable $callback)
    {
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $callback();
        }
        return $this->cache[$key];
    }
}
```

#### Memory Management

```php
class MemoryManager
{
    public function optimize(): void
    {
        // Clear unnecessary variables
        unset($this->temporaryData);
        
        // Force garbage collection
        gc_collect_cycles();
    }
}
```

---

**🔍 Need Help?**: Create an issue on GitHub or ask in discussions for development-related questions.
