# Testing Guide

## Overview

The Klytron PHP Deployment Kit includes a comprehensive test suite to ensure reliability and catch regressions.

## Test Structure

```
tests/
├── Unit/                    # Unit tests for individual components
│   ├── CoreFunctionsTest.php
│   ├── LaravelRecipeTest.php
│   └── CommandsTest.php
├── Integration/             # Integration tests for workflows
│   └── DeploymentWorkflowTest.php
└── SQLiteSetterTest.md     # Manual testing guide
```

## Running Tests

### Run All Tests
```bash
composer test
```

### Run Specific Test Suite
```bash
# Unit tests only
vendor/bin/phpunit tests/Unit

# Integration tests only
vendor/bin/phpunit tests/Integration

# Specific test file
vendor/bin/phpunit tests/Unit/CoreFunctionsTest.php
```

### Run with Coverage
```bash
vendor/bin/phpunit --coverage-html coverage-html
```

## Test Categories

### Unit Tests
- **CoreFunctionsTest**: Tests core deployment kit functions
- **LaravelRecipeTest**: Tests Laravel-specific recipe functionality
- **CommandsTest**: Tests Artisan commands

### Integration Tests
- **DeploymentWorkflowTest**: Tests complete deployment workflows
- Validates file structure
- Checks configuration loading
- Verifies service providers

## Writing New Tests

### Unit Test Example
```php
public function testFunctionName(): void
{
    // Arrange
    $expected = 'expected_value';
    
    // Act
    $actual = functionBeingTested();
    
    // Assert
    $this->assertEquals($expected, $actual);
}
```

### Integration Test Example
```php
public function testWorkflowIntegration(): void
{
    // Test that components work together
    $this->assertFileExists($expectedFile);
    $this->assertStringContainsString($expectedContent, file_get_contents($expectedFile));
}
```

## Test Environment

Tests use mock functions when Deployer is not available:
```php
if (!function_exists('Deployer\set')) {
    // Mock functions for testing
    eval('
        namespace Deployer {
            function set($key, $value = null) { /* mock implementation */ }
            function get($key, $default = null) { /* mock implementation */ }
        }
    ');
}
```

## Continuous Integration

The test suite is designed to run in CI/CD environments:
- No external dependencies required for basic tests
- Mock functions provide isolation
- Fast execution time
- Clear error reporting

## Coverage Goals

- **Core Functions**: 90%+ coverage
- **Commands**: 85%+ coverage  
- **Integration**: 80%+ coverage

## Debugging Tests

### Run in Verbose Mode
```bash
vendor/bin/phpunit -v
```

### Stop on First Failure
```bash
vendor/bin/phpunit --stop-on-failure
```

### Debug with Xdebug
```bash
php -d xdebug.mode=debug vendor/bin/phpunit
```

## Best Practices

1. **Test One Thing**: Each test should validate a single behavior
2. **Use Descriptive Names**: Test names should describe what they test
3. **Mock External Dependencies**: Use mocks for external services
4. **Clean Up**: Reset state between tests
5. **Edge Cases**: Test error conditions and edge cases

## Troubleshooting

### Common Issues

**"Deployer function not found"**
- Tests include mock functions for Deployer
- Ensure proper namespace usage

**"File not found" errors**
- Check file paths in test setup
- Verify working directory

**Permission errors**
- Ensure test directory is writable
- Check file permissions

### Getting Help

- Check existing tests for examples
- Review PHPUnit documentation
- Use debug output to identify issues
