# Klytron Deployer Package Migration Guide

This guide outlines the migration from git submodule/directory-based installation to a proper Composer package.

## 🎯 Migration Strategy

### Phase 1: Prepare Package Structure

1. **Restructure for Composer:**
```bash
mkdir -p src/Recipe
mv deployment-kit.php src/Recipe/KlytronDeployer.php
mv klytron-tasks.php src/Recipe/KlytronDeployerTasks.php
```

2. **Update composer.json:**
```json
{
    "name": "klytron/php-deployment-kit",
    "description": "A comprehensive deployment library for PHP applications",
    "type": "library",
    "require": {
        "php": "^8.1",
        "deployer/deployer": "^7.0"
    },
    "autoload": {
        "files": ["src/Recipe/KlytronDeployer.php"]
    }
}
```

3. **Create recipe file for Deployer:**
```php
// src/Recipe/deployment-kit.php
<?php
require_once __DIR__ . '/KlytronDeployer.php';
```

### Phase 2: Publish to Packagist

1. **Tag a release:**
```bash
git tag -a v1.0.0 -m "First stable release"
git push origin v1.0.0
```

2. **Submit to Packagist:**
   - Go to https://packagist.org/packages/submit
   - Submit your GitHub repository URL
   - Packagist will auto-update on new tags

### Phase 3: Update Projects

#### Old Method (Current):
```php
require_once __DIR__ . '/deployment-kit/deployment-kit.php';
```

#### New Method (Composer):
```bash
composer require klytron/php-deployment-kit --dev
```

```php
require 'vendor/klytron/php-deployment-kit/src/Recipe/KlytronDeployer.php';
// or
require 'recipe/deployment-kit.php';
```

### Phase 4: Backward Compatibility

Keep both methods working during transition:

```php
// In deploy.php - auto-detect installation method
if (file_exists(__DIR__ . '/vendor/klytron/php-deployment-kit/src/Recipe/KlytronDeployer.php')) {
    // Composer installation
    require 'vendor/klytron/php-deployment-kit/src/Recipe/KlytronDeployer.php';
} elseif (file_exists(__DIR__ . '/deployment-kit/deployment-kit.php')) {
    // Git submodule installation
    require_once __DIR__ . '/deployment-kit/deployment-kit.php';
} else {
    throw new Exception('Klytron Deployer not found. Install via: composer require klytron/php-deployment-kit --dev');
}
```

## 🚀 Benefits of Package Approach

### For You (Maintainer):
- ✅ **Centralized Updates**: Update once, all projects get it
- ✅ **Version Control**: Semantic versioning and release management
- ✅ **Statistics**: Download stats and usage metrics
- ✅ **Professional**: Standard PHP package distribution

### For Users:
- ✅ **Easy Installation**: `composer require klytron/php-deployment-kit`
- ✅ **Automatic Updates**: `composer update` gets latest
- ✅ **Version Locking**: Can pin to specific versions
- ✅ **Dependency Management**: Composer handles everything

## 📋 Implementation Checklist

### Repository Preparation:
- [ ] Restructure files for Composer
- [ ] Update composer.json with proper package info
- [ ] Create recipe file for Deployer integration
- [ ] Update README with new installation instructions
- [ ] Add CHANGELOG.md for version tracking
- [ ] Tag first stable release (v1.0.0)

### Packagist Setup:
- [ ] Submit package to Packagist
- [ ] Configure auto-update webhook
- [ ] Verify package appears correctly

### Project Migration:
- [ ] Update all existing projects to use Composer
- [ ] Remove git submodules
- [ ] Update deployment scripts
- [ ] Test deployments with new package

### Documentation:
- [ ] Update all documentation
- [ ] Create migration guide for users
- [ ] Update examples and templates
- [ ] Announce the change

## 🔄 Migration Timeline

### Week 1: Preparation
- Restructure repository
- Update composer.json
- Test package structure locally

### Week 2: Publishing
- Tag and publish to Packagist
- Update documentation
- Create migration guide

### Week 3: Project Updates
- Migrate all your projects
- Test deployments
- Fix any issues

### Week 4: Announcement
- Announce new package method
- Update all documentation
- Deprecate old installation methods

## 🛠️ Technical Implementation

### New File Structure:
```
deployment-kit/
├── src/
│   └── Recipe/
│       ├── KlytronDeployer.php      # Main library
│       ├── KlytronDeployerTasks.php # Task definitions
│       └── deployment-kit.php     # Recipe file
├── examples/                       # Keep examples
├── templates/                      # Keep templates
├── docs/                          # Keep documentation
├── composer.json                   # Package definition
├── README.md                       # Updated instructions
└── CHANGELOG.md                    # Version history
```

### Version Management:
```bash
# Release new versions
git tag -a v1.1.0 -m "Add new features"
git push origin v1.1.0

# Packagist auto-updates within minutes
```

### Project Usage:
```bash
# In any project
composer require klytron/php-deployment-kit --dev
composer update klytron/php-deployment-kit  # Get latest version
```

This approach ensures all your projects always have access to the latest version while maintaining professional package management standards.
