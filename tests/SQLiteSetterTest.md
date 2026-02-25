# SQLite Setter Command Test Guide

## Overview

This guide helps you test the enhanced `LaweitechSqliteSetter` command that now derives database filenames from configuration or app name instead of using hardcoded values.

## Test Scenarios

### 1. Basic Auto-Detection Test

**Setup:**
```php
// In config/app.php
'name' => 'My Blog App',
'env' => 'local',
```

**Command:**
```bash
php artisan app:sqlite-setup
```

**Expected Result:**
- Database name: `my_blog_app_local.sqlite`
- Location: `storage/app/database/`
- Full path: `storage/app/database/my_blog_app_local.sqlite`

### 2. Custom Database Name Test

**Command:**
```bash
php artisan app:sqlite-setup --database=custom_blog
```

**Expected Result:**
- Database name: `custom_blog.sqlite`
- Location: `storage/app/database/`
- Full path: `storage/app/database/custom_blog.sqlite`

### 3. Custom Location Test

**Command:**
```bash
php artisan app:sqlite-setup --location=storage/databases
```

**Expected Result:**
- Database name: `{app_name}_{env}.sqlite`
- Location: `storage/app/storage/databases/`
- Directory created if it doesn't exist

### 4. Force Recreate Test

**Setup:**
```bash
# First create a database
php artisan app:sqlite-setup --database=test_db

# Then force recreate it
php artisan app:sqlite-setup --database=test_db --force
```

**Expected Result:**
- Existing database is deleted
- New database is created
- Warning message about force recreation

### 5. Config-Based Detection Test

**Setup:**
```php
// In config/database.php
'connections' => [
    'sqlite' => [
        'driver' => 'sqlite',
        'database' => storage_path('app/my_configured_db.sqlite'),
        // ...
    ],
],
```

**Command:**
```bash
php artisan app:sqlite-setup
```

**Expected Result:**
- Database name: `my_configured_db.sqlite`
- Uses the filename from the config

### 6. Production Environment Test

**Setup:**
```php
// In config/app.php
'name' => 'E-Commerce Store',
'env' => 'production',
```

**Command:**
```bash
php artisan app:sqlite-setup
```

**Expected Result:**
- Database name: `e_commerce_store_production.sqlite`
- Proper slug formatting with underscores

## Testing Commands

### Manual Testing Script

```bash
#!/bin/bash

echo "🧪 Testing Enhanced SQLite Setter Command"
echo "=========================================="

# Test 1: Basic auto-detection
echo "Test 1: Basic auto-detection"
php artisan app:sqlite-setup
echo ""

# Test 2: Custom database name
echo "Test 2: Custom database name"
php artisan app:sqlite-setup --database=test_custom
echo ""

# Test 3: Custom location
echo "Test 3: Custom location"
php artisan app:sqlite-setup --database=test_location --location=storage/test_db
echo ""

# Test 4: Force recreate
echo "Test 4: Force recreate"
php artisan app:sqlite-setup --database=test_force --force
echo ""

# Test 5: Help command
echo "Test 5: Help command"
php artisan help app:sqlite-setup
echo ""

echo "✅ All tests completed!"
```

### Verification Commands

```bash
# Check created files
find storage/app -name "*.sqlite" -type f

# Check file permissions
ls -la storage/app/database/*.sqlite

# Check file sizes (should be 0 for new files)
du -h storage/app/database/*.sqlite

# Test database connectivity
php artisan tinker
# In tinker:
# DB::connection('sqlite')->select('SELECT 1 as test');
```

## Expected Behaviors

### ✅ Correct Behaviors

1. **Smart Naming**: Database names are derived from app config
2. **Slug Formatting**: App names are converted to valid filenames
3. **Environment Awareness**: Includes environment in filename
4. **Directory Creation**: Creates directories if they don't exist
5. **Permission Setting**: Sets 664 permissions on created files
6. **Force Recreation**: Properly handles existing files with --force
7. **Custom Options**: Respects --database and --location options
8. **Config Detection**: Uses database config when available

### ❌ Incorrect Behaviors (Fixed)

1. ~~**Hardcoded Names**: No longer uses `picture_gallery_adx_redirector.sqlite`~~
2. ~~**Fixed Location**: No longer hardcoded to `database/` directory~~
3. ~~**No Configuration**: Now respects app and database configuration~~

## Integration Testing

### With Klytron Deployer

```php
// In deploy.php
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'sqlite',
    // ... other config
]);

// The SQLite setup will automatically use your app configuration
```

### With Different App Names

Test with various app names to ensure proper slug conversion:

```php
// Test cases
'My Blog App' => 'my_blog_app_local.sqlite'
'E-Commerce Store!' => 'e_commerce_store_local.sqlite'
'API Backend 2.0' => 'api_backend_2_0_local.sqlite'
'Simple CMS' => 'simple_cms_local.sqlite'
```

## Troubleshooting

### Common Issues

1. **Permission Denied**: Ensure storage directory is writable
2. **Directory Not Found**: Command should create directories automatically
3. **File Already Exists**: Use --force to recreate existing files
4. **Invalid Characters**: App names are automatically slugified

### Debug Commands

```bash
# Check app configuration
php artisan config:show app.name
php artisan config:show app.env

# Check database configuration
php artisan config:show database.connections.sqlite

# Check storage permissions
ls -la storage/app/

# Test file creation manually
touch storage/app/database/test.sqlite
ls -la storage/app/database/test.sqlite
```

## Success Criteria

The enhanced SQLite setter command is working correctly if:

1. ✅ No hardcoded database names are used
2. ✅ Database names are derived from app configuration
3. ✅ Custom options (--database, --location, --force) work properly
4. ✅ Directories are created automatically
5. ✅ File permissions are set correctly (664)
6. ✅ Environment-specific naming works
7. ✅ Config-based detection works
8. ✅ Proper error handling and user feedback
