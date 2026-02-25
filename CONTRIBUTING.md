# 🤝 Contributing to Klytron Deployer

Thank you for your interest in contributing to Klytron Deployer! This guide will help you get started with contributing to the project.

## 🎯 How to Contribute

There are many ways to contribute to Klytron Deployer:

- **🐛 Report Bugs** - Help us identify and fix issues
- **💡 Suggest Features** - Propose new features and improvements
- **📝 Improve Documentation** - Help make our docs better
- **🔧 Submit Code** - Contribute code improvements and new features
- **🧪 Write Tests** - Add tests to improve code quality
- **📚 Share Examples** - Contribute real-world deployment examples
- **🌐 Help Others** - Answer questions and help other users

## 🚀 Getting Started

### Prerequisites

Before contributing, ensure you have:

- **PHP 8.1+** installed
- **Composer** installed and configured
- **Git** installed and configured
- **SSH** access to servers for testing
- **Basic knowledge** of PHP and deployment concepts

### Development Setup

1. **Fork the Repository**

```bash
# Fork the repository on GitHub
# Then clone your fork
git clone https://github.com/YOUR_USERNAME/deployment-kit.git
cd deployment-kit
```

2. **Install Dependencies**

```bash
# Install dependencies
composer install

# Install development dependencies
composer install --dev
```

3. **Set Up Development Environment**

```bash
# Create development branch
git checkout -b feature/your-feature-name

# Set up pre-commit hooks (optional)
cp .git/hooks/pre-commit.sample .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

4. **Run Tests**

```bash
# Run all tests
composer test

# Run specific test suite
vendor/bin/phpunit --filter TestClassName

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

## 📋 Development Guidelines

### Code Standards

We follow PSR-12 coding standards and use PHPStan for static analysis.

#### PHP Code Standards

```php
<?php

declare(strict_types=1);

namespace Klytron\Deployer;

/**
 * Example class following our coding standards.
 *
 * @package Klytron\Deployer
 * @author Your Name <your.email@example.com>
 */
class ExampleClass
{
    private string $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function setProperty(string $property): self
    {
        $this->property = $property;

        return $this;
    }
}
```

#### Code Quality Checks

```bash
# Run PHPStan static analysis
composer analyse

# Run PHP CS Fixer
composer cs-fix

# Run PHP CS Fixer (dry run)
composer cs-check

# Run all quality checks
composer check
```

### Documentation Standards

#### Markdown Guidelines

- Use clear, concise language
- Include code examples where appropriate
- Use proper heading hierarchy
- Add emojis for visual appeal
- Keep line length under 100 characters
- Use proper code blocks with language specification

#### Example Documentation

```markdown
# 🎯 Feature Name

Brief description of the feature.

## Usage

```php
// Example code
klytron_configure_feature([
    'option' => 'value',
]);
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `option` | string | `'default'` | Description of option |

## Examples

### Basic Example

```php
// Basic usage example
```

### Advanced Example

```php
// Advanced usage example
```
```

### Testing Guidelines

#### Writing Tests

```php
<?php

declare(strict_types=1);

namespace Klytron\Deployer\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test class for ExampleClass.
 *
 * @package Klytron\Deployer\Tests
 */
class ExampleClassTest extends TestCase
{
    public function testGetProperty(): void
    {
        $example = new ExampleClass('test');
        
        $this->assertEquals('test', $example->getProperty());
    }

    public function testSetProperty(): void
    {
        $example = new ExampleClass('old');
        $result = $example->setProperty('new');
        
        $this->assertEquals('new', $example->getProperty());
        $this->assertSame($example, $result);
    }
}
```

#### Test Naming Conventions

- Test methods should be descriptive
- Use `test` prefix for test methods
- Group related tests in the same class
- Use data providers for multiple test cases

#### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/phpunit tests/ExampleClassTest.php

# Run tests with verbose output
vendor/bin/phpunit --verbose

# Run tests with coverage
vendor/bin/phpunit --coverage-html coverage/
```

## 🔧 Development Workflow

### Feature Development

1. **Create Feature Branch**

```bash
git checkout -b feature/your-feature-name
```

2. **Make Changes**

- Write your code
- Add tests for new functionality
- Update documentation
- Follow coding standards

3. **Test Your Changes**

```bash
# Run tests
composer test

# Run static analysis
composer analyse

# Run code style checks
composer cs-check

# Run all checks
composer check
```

4. **Commit Your Changes**

```bash
# Add your changes
git add .

# Commit with descriptive message
git commit -m "feat: add new feature description

- Add new functionality
- Include tests
- Update documentation

Closes #123"
```

5. **Push and Create Pull Request**

```bash
git push origin feature/your-feature-name
```

### Bug Fixes

1. **Create Bug Fix Branch**

```bash
git checkout -b fix/bug-description
```

2. **Fix the Bug**

- Identify the root cause
- Write a minimal fix
- Add tests to prevent regression
- Update documentation if needed

3. **Test the Fix**

```bash
# Run existing tests
composer test

# Add new tests for the fix
# Ensure all tests pass
```

4. **Commit the Fix**

```bash
git commit -m "fix: resolve bug description

- Fix the specific issue
- Add regression tests
- Update affected documentation

Fixes #123"
```

### Documentation Improvements

1. **Create Documentation Branch**

```bash
git checkout -b docs/improvement-description
```

2. **Improve Documentation**

- Fix typos and grammar
- Add missing information
- Improve examples
- Update outdated content

3. **Commit Documentation**

```bash
git commit -m "docs: improve documentation

- Fix typos and grammar
- Add missing examples
- Update outdated information

Closes #123"
```

## 📝 Pull Request Process

### Before Submitting

1. **Ensure Quality**

```bash
# Run all quality checks
composer check

# Ensure tests pass
composer test

# Check code style
composer cs-check
```

2. **Update Documentation**

- Update relevant documentation
- Add examples if needed
- Update changelog if applicable

3. **Test Your Changes**

- Test in different environments
- Test with different PHP versions
- Test with different frameworks

### Pull Request Guidelines

#### Title Format

Use conventional commit format:

```
type(scope): description

Examples:
feat(core): add new configuration option
fix(laravel): resolve storage permission issue
docs(api): update API documentation
test(core): add tests for new feature
```

#### Description Template

```markdown
## Description

Brief description of the changes.

## Type of Change

- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing

- [ ] Tests pass locally
- [ ] Tests added for new functionality
- [ ] Documentation updated

## Checklist

- [ ] Code follows the style guidelines
- [ ] Self-review of code completed
- [ ] Code is commented, particularly in hard-to-understand areas
- [ ] Corresponding changes to documentation made
- [ ] Changes generate no new warnings
- [ ] Tests added that prove fix is effective or feature works
- [ ] New and existing unit tests pass locally

## Related Issues

Closes #123
Fixes #456
```

### Review Process

1. **Automated Checks**

- CI/CD pipeline runs tests
- Code quality checks are performed
- Coverage reports are generated

2. **Code Review**

- Maintainers review your code
- Address feedback and suggestions
- Make requested changes

3. **Merge**

- Once approved, your PR will be merged
- Your changes will be included in the next release

## 🐛 Reporting Bugs

### Bug Report Template

```markdown
## Bug Description

Clear and concise description of the bug.

## Steps to Reproduce

1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

## Expected Behavior

What you expected to happen.

## Actual Behavior

What actually happened.

## Environment

- OS: [e.g. Ubuntu 20.04]
- PHP Version: [e.g. 8.1.0]
- Klytron Deployer Version: [e.g. 1.0.0]
- Deployer Version: [e.g. 7.0.0]

## Additional Context

Add any other context about the problem here.

## Logs

Include relevant logs if applicable.
```

### Before Reporting

1. **Check Existing Issues**

- Search existing issues for similar problems
- Check if the issue has already been reported
- Look for workarounds or solutions

2. **Reproduce the Issue**

- Ensure you can reproduce the issue consistently
- Try with minimal configuration
- Test with different environments

3. **Gather Information**

- Collect relevant logs
- Note your environment details
- Document exact steps to reproduce

## 💡 Suggesting Features

### Feature Request Template

```markdown
## Feature Description

Clear and concise description of the feature.

## Problem Statement

What problem does this feature solve?

## Proposed Solution

How would you like this feature to work?

## Alternative Solutions

Any alternative solutions you've considered.

## Use Cases

Describe specific use cases for this feature.

## Implementation Ideas

Any thoughts on how this could be implemented.

## Additional Context

Add any other context or screenshots.
```

### Before Suggesting

1. **Check Existing Features**

- Search existing issues for similar requests
- Check if the feature already exists
- Look for workarounds

2. **Think Through the Feature**

- Consider the use cases
- Think about implementation complexity
- Consider backward compatibility

3. **Provide Context**

- Explain why the feature is needed
- Provide concrete examples
- Consider edge cases

## 📚 Contributing Examples

### Example Guidelines

1. **Real-World Based**

- Examples should be based on real deployments
- Use realistic configuration values
- Include common scenarios

2. **Well Documented**

- Include clear comments
- Explain configuration choices
- Document any assumptions

3. **Tested**

- Test examples before submitting
- Ensure they work as expected
- Include test instructions

### Example Template

```php
<?php
/**
 * Example Name and Description
 *
 * This example demonstrates how to deploy a [type] application
 * with [specific features] using Klytron Deployer.
 *
 * @package ExamplePackage
 * @author Your Name <your.email@example.com>
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
klytron_configure_app('example-app', 'git@github.com:user/example-app.git');

// Path configuration
klytron_set_paths('/var/www', '/var/www/html');

// Project capabilities
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    // Add other configuration options
]);

// Host configuration
klytron_configure_host('example.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
    // Add other host options
]);

///////////////////////////////////////////////////////////////////////////////
// SHARED FILES/DIRECTORIES
///////////////////////////////////////////////////////////////////////////////

// Configure shared files and directories
klytron_configure_shared_files([
    '.env',
    // Add other shared files
]);

klytron_configure_shared_dirs([
    'storage',
    // Add other shared directories
]);

///////////////////////////////////////////////////////////////////////////////
// CUSTOM TASKS (IF ANY)
///////////////////////////////////////////////////////////////////////////////

// Add any custom tasks specific to this example
```

## 🏷️ Release Process

### Versioning

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR** version for incompatible API changes
- **MINOR** version for backwards-compatible functionality
- **PATCH** version for backwards-compatible bug fixes

### Release Checklist

1. **Update Version**

```bash
# Update version in composer.json
# Update version in deployment-kit.php
# Update CHANGELOG.md
```

2. **Create Release Branch**

```bash
git checkout -b release/v1.0.0
```

3. **Final Testing**

```bash
# Run all tests
composer test

# Run quality checks
composer check

# Test in different environments
```

4. **Create Release**

```bash
# Tag the release
git tag -a v1.0.0 -m "Release version 1.0.0"

# Push tag
git push origin v1.0.0

# Create GitHub release
```

## 📞 Getting Help

### Communication Channels

- **GitHub Issues**: For bug reports and feature requests
- **GitHub Discussions**: For questions and general discussion
- **Email**: hi@klytron.com

### Before Asking for Help

1. **Check Documentation**

- Read the relevant documentation
- Check examples and templates
- Look for troubleshooting guides

2. **Search Issues**

- Search existing issues for similar problems
- Check if your question has been answered
- Look for workarounds

3. **Provide Context**

- Include your environment details
- Provide relevant configuration
- Include error messages and logs

## 🙏 Recognition

### Contributors

All contributors are recognized in:

- **README.md** - For significant contributions
- **CHANGELOG.md** - For all contributions
- **GitHub Contributors** - Automatic recognition

### Types of Recognition

- **Code Contributors** - Direct code contributions
- **Documentation Contributors** - Documentation improvements
- **Bug Reporters** - Quality bug reports
- **Feature Requesters** - Well-thought-out feature requests
- **Community Helpers** - Helping other users

## 📄 License

By contributing to Klytron Deployer, you agree that your contributions will be licensed under the MIT License.

## 🎯 Next Steps

1. **Fork the Repository**
2. **Set Up Development Environment**
3. **Choose an Issue to Work On**
4. **Make Your Changes**
5. **Submit a Pull Request**

Thank you for contributing to Klytron Deployer! 🚀
