# 🔄 Backup & Restore Guide

[← Back to Documentation](README.md)

## 📋 Table of Contents

- [Overview](#overview)
- [Automatic Backups](#automatic-backups)
- [Manual Backups](#manual-backups)
- [Database Backups](#database-backups)
- [File System Backups](#file-system-backups)
- [Restore Procedures](#restore-procedures)
- [Backup Locations](#backup-locations)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)

## 🌟 Overview

Klytron Deployer includes comprehensive backup and restore capabilities to ensure your data is safe during deployments. The system automatically creates backups before critical operations and provides tools for manual backup management.

### 🛡️ Key Features

- **Automatic pre-deployment backups**
- **Database backup support** (MySQL, PostgreSQL, SQLite)
- **File system backups** with compression
- **Rollback capabilities** for failed deployments
- **Backup rotation** and cleanup
- **Cross-platform compatibility**

## 🔄 Automatic Backups

### Pre-Deployment Backups

Klytron Deployer automatically creates backups before each deployment:

```bash
# Automatic backup is created during deployment
vendor/bin/dep deploy
```

**What gets backed up:**
- Database (if configured)
- Application files (current version)
- Environment files
- Configuration files

### Backup Triggers

Automatic backups are created when:
- Running `dep deploy`
- Running `dep rollback`
- Running `dep backup:create`
- Before critical operations

## 🛠️ Manual Backups

### Create Manual Backup

```bash
# Create a complete backup
vendor/bin/dep backup:create

# Create database-only backup
vendor/bin/dep backup:database

# Create files-only backup
vendor/bin/dep backup:files
```

### Backup with Custom Name

```bash
# Create backup with custom name
vendor/bin/dep backup:create --name="pre-migration-backup"

# Create timestamped backup
vendor/bin/dep backup:create --timestamp
```

## 🗄️ Database Backups

### Supported Databases

| Database | Backup Command | Restore Command |
|----------|---------------|-----------------|
| **MySQL** | `mysqldump` | `mysql` |
| **PostgreSQL** | `pg_dump` | `psql` |
| **SQLite** | File copy | File copy |

### Database Backup Configuration

```php
// In your deploy.php
klytron_configure_database([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'myapp',
    'username' => 'dbuser',
    'password' => 'dbpass',
    'backup_enabled' => true,
    'backup_compress' => true,
]);
```

### Database Backup Options

```php
klytron_configure_database([
    // ... database config
    'backup_options' => [
        'include_tables' => ['users', 'posts', 'comments'],
        'exclude_tables' => ['temp_*', 'cache_*'],
        'single_transaction' => true,
        'routines' => true,
        'triggers' => true,
    ],
]);
```

## 📁 File System Backups

### Backup Scope

File system backups include:
- Application source code
- Configuration files
- Environment files
- Uploaded files (if configured)
- Custom directories

### File Backup Configuration

```php
// Configure file backup options
klytron_configure_backup([
    'files' => [
        'enabled' => true,
        'compress' => true,
        'include_dirs' => [
            'app/',
            'config/',
            'storage/app/public/',
        ],
        'exclude_dirs' => [
            'storage/logs/',
            'storage/framework/cache/',
            'node_modules/',
        ],
    ],
]);
```

## 🔄 Restore Procedures

### Restore from Backup

```bash
# List available backups
vendor/bin/dep backup:list

# Restore complete backup
vendor/bin/dep backup:restore --name="backup-2024-01-15-10-30-00"

# Restore database only
vendor/bin/dep backup:restore --name="backup-2024-01-15-10-30-00" --database-only

# Restore files only
vendor/bin/dep backup:restore --name="backup-2024-01-15-10-30-00" --files-only
```

### Emergency Rollback

```bash
# Rollback to previous deployment (includes backup restore)
vendor/bin/dep rollback

# Rollback to specific version
vendor/bin/dep rollback --version=1
```

### Manual Restore

```bash
# Restore database manually
vendor/bin/dep backup:restore-database --file="backup-db-2024-01-15.sql"

# Restore files manually
vendor/bin/dep backup:restore-files --file="backup-files-2024-01-15.tar.gz"
```

## 📍 Backup Locations

### Default Locations

```
/var/www/backups/
├── database/
│   ├── backup-2024-01-15-10-30-00.sql.gz
│   └── backup-2024-01-15-11-45-00.sql.gz
├── files/
│   ├── backup-2024-01-15-10-30-00.tar.gz
│   └── backup-2024-01-15-11-45-00.tar.gz
└── complete/
    ├── backup-2024-01-15-10-30-00/
    └── backup-2024-01-15-11-45-00/
```

### Custom Backup Location

```php
// Configure custom backup location
klytron_configure_backup([
    'backup_path' => '/var/backups/myapp',
    'retention' => [
        'days' => 30,
        'max_backups' => 50,
    ],
]);
```

## ⚙️ Configuration

### Backup Configuration Options

```php
klytron_configure_backup([
    // Enable/disable backups
    'enabled' => true,
    
    // Backup location
    'backup_path' => '/var/www/backups',
    
    // Compression
    'compress' => true,
    
    // Retention policy
    'retention' => [
        'days' => 30,
        'max_backups' => 50,
        'auto_cleanup' => true,
    ],
    
    // Database backup
    'database' => [
        'enabled' => true,
        'compress' => true,
        'include_tables' => [],
        'exclude_tables' => ['temp_*'],
    ],
    
    // File backup
    'files' => [
        'enabled' => true,
        'compress' => true,
        'include_dirs' => [],
        'exclude_dirs' => ['node_modules', 'storage/logs'],
    ],
    
    // Notification
    'notifications' => [
        'on_success' => true,
        'on_failure' => true,
        'email' => 'admin@example.com',
    ],
]);
```

### Environment-Specific Configuration

```php
// Production backup settings
klytron_configure_backup([
    'enabled' => true,
    'retention' => ['days' => 90, 'max_backups' => 100],
    'compress' => true,
]);

// Development backup settings
klytron_configure_backup([
    'enabled' => false, // Disable in development
    'retention' => ['days' => 7, 'max_backups' => 10],
]);
```

## 🔧 Troubleshooting

### Common Issues

#### Backup Fails

```bash
# Check backup permissions
vendor/bin/dep backup:test

# Check available disk space
vendor/bin/dep backup:check-space

# View backup logs
vendor/bin/dep backup:logs
```

#### Database Backup Issues

```bash
# Test database connection
vendor/bin/dep backup:test-database

# Check database permissions
vendor/bin/dep backup:check-database-permissions
```

#### Restore Issues

```bash
# Validate backup integrity
vendor/bin/dep backup:validate --name="backup-name"

# Dry run restore
vendor/bin/dep backup:restore --dry-run --name="backup-name"
```

### Backup Commands Reference

| Command | Description |
|---------|-------------|
| `backup:create` | Create a new backup |
| `backup:list` | List available backups |
| `backup:restore` | Restore from backup |
| `backup:delete` | Delete backup |
| `backup:cleanup` | Clean up old backups |
| `backup:test` | Test backup functionality |
| `backup:validate` | Validate backup integrity |

### Backup File Formats

| Type | Format | Compression |
|------|--------|-------------|
| **Database** | `.sql` | `.gz` |
| **Files** | `.tar` | `.gz` |
| **Complete** | Directory | `.tar.gz` |

## 📚 Related Documentation

- [Installation Guide](installation.md)
- [Configuration Guide](configuration-reference.md)
- [Task Reference](task-reference.md)
- [Troubleshooting Guide](troubleshooting.md)

---

**💡 Pro Tip**: Always test your backup and restore procedures in a staging environment before relying on them in production.

**🔒 Security Note**: Ensure backup files are stored securely and access is restricted to authorized personnel only.
