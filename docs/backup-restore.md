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
- Running `dep deploy` (when `shouldBackupBeforeDeployment` is enabled)
- Running `dep rollback` (Deployer built-in — returns to the previous release)

## 🛠️ Manual Backups

### Create Manual Backup

```bash
# Create a pre-deployment snapshot of the current release
# (plus a database dump when shouldBackupDatabase is true)
vendor/bin/dep klytron:deploy:backup:create

# Laravel projects can use the Laravel variant instead
vendor/bin/dep klytron:laravel:deploy:backup:create
```

### Backup with Custom Name

Backups are timestamped automatically (`backup_before_deploy_<timestamp>`)
under `{{deploy_path}}/backups`. The task takes no `--name`/`--timestamp`
options — rename the directory afterwards if you need a custom label.

## 🗄️ Database Backups

### Supported Databases

| Database | Backup Command | Restore Command |
|----------|---------------|-----------------|
| **MySQL** | `mysqldump` | `mysql` |
| **PostgreSQL** | `pg_dump` | `psql` |
| **SQLite** | File copy | File copy |

### Database Backup Configuration

```php
// In your deploy.php — real signature: klytron_configure_database(string $type, array $config)
klytron_configure_database('mysql', [
    'import_path' => 'database/live-db-exports', // used by db:import tasks
    'supports_migrations' => true,
    'supports_seeders' => true,
]);

// Database credentials for dumps are plain Deployer config (read by
// klytron:deploy:backup:create) — note the `db_pass` key:
set('db_host', 'localhost');
set('db_port', '3306');
set('db_name', 'myapp');
set('db_user', 'dbuser');
set('db_pass', 'dbpass');
```
```

### Database Backup Options

```php
// There is no per-table backup filtering in this package. The database dump
// is a full mysqldump/pg_dump written next to the release snapshot whenever
// both flags below are true:
set('shouldBackupBeforeDeployment', true);
set('shouldBackupDatabase', true);
```
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
// There is no klytron_configure_backup() helper in this package.
// File backups are the release snapshot itself (cp -r of current),
// controlled by these real flags/keys:
set('shouldBackupBeforeDeployment', true); // snapshot current release first
set('backup_path', '{{deploy_path}}/backups'); // where snapshots live
set('backup_keep', 5);                         // how many to retain
```
```

## 🔄 Restore Procedures

### Restore from Backup

There are no `backup:list` / `backup:restore` commands in this package.
Use Deployer's built-in rollback, which points `current` at the previous
successful release:

```bash
# Roll back to the previous release
vendor/bin/dep rollback

# Roll back to a specific release (Deployer built-in option)
vendor/bin/dep rollback -o rollback_candidate=123
```

Release snapshots taken by `klytron:deploy:backup:create` live under
`{{deploy_path}}/backups/backup_before_deploy_<timestamp>/` — copy files
or import the `database_<timestamp>.sql` dump manually when you need a
partial restore.

### Emergency Rollback

```bash
# Rollback to previous deployment (includes backup restore)
vendor/bin/dep rollback

# Rollback to specific version
vendor/bin/dep rollback --version=1
```

### Manual Restore

```bash
# Import a database dump from a snapshot manually
mysql -h localhost -u dbuser -p myapp < backup_before_deploy_<timestamp>/database_<timestamp>.sql

# Copy release files back from a snapshot manually
cp -r {{deploy_path}}/backups/backup_before_deploy_<timestamp> {{deploy_path}}/current-restore
```

## 📍 Backup Locations

### Default Locations

Snapshots live under `{{deploy_path}}/backups` (override with
`set('backup_path', ...)`). Each run creates one timestamped directory
holding a copy of `current` plus the database dump when enabled:

```
{{deploy_path}}/backups/
├── backup_before_deploy_2024-01-15_10-30-00/
│   ├── (copy of current release files)
│   └── database_2024-01-15_10-30-00.sql
└── backup_before_deploy_2024-01-15_11-45-00/
    ├── (copy of current release files)
    └── database_2024-01-15_11-45-00.sql
```

### Custom Backup Location

```php
// Configure a custom backup location (real keys)
set('backup_path', '/var/backups/myapp');
set('backup_keep', 50);
```
```

## ⚙️ Configuration

### Backup Configuration Options

```php
// Real backup configuration — plain Deployer config, no helper wrapper:
set('shouldBackupBeforeDeployment', true); // snapshot current release first
set('shouldBackupDatabase', true);         // include a database dump
set('backup_path', '{{deploy_path}}/backups');
set('backup_keep', 5);

// Database credentials used for the dump (note the `db_pass` key):
set('db_host', 'localhost');
set('db_port', '3306');
set('db_name', 'myapp');
set('db_user', 'dbuser');
set('db_pass', 'dbpass');
```
```

### Environment-Specific Configuration

```php
// Enable snapshots for production, skip them for development
set('shouldBackupBeforeDeployment', true);
set('shouldBackupDatabase', true);
```
```

## 🔧 Troubleshooting

### Common Issues

#### Backup Fails

```bash
# Inspect the deployment configuration without connecting
vendor/bin/dep klytron:deploy:info

# Check available disk space on the server before retrying
ssh deploy@your-server.com 'df -h /var/www'
```

#### Database Backup Issues

```bash
# Verify database credentials from your local machine first
mysql -h <db-host> -u <db-user> -p -e 'SELECT 1'

# Then confirm deploy.php sets the same values via
# set('db_host', ...), set('db_name', ...), set('db_user', ...), set('db_pass', ...)
```

#### Restore Issues

```bash
# List snapshots on the server (backup_path defaults to {{deploy_path}}/backups)
ssh deploy@your-server.com 'ls -la /var/www/<app>/backups'

# Roll back to the previous release (Deployer built-in)
vendor/bin/dep rollback
```

### Backup Commands Reference

| Command | Description |
|---------|-------------|
| `klytron:deploy:backup:create` | Snapshot current release (+ DB dump when enabled) |
| `klytron:laravel:deploy:backup:create` | Laravel variant of the pre-deploy snapshot |
| `rollback` | Return `current` to the previous release (Deployer built-in) |
| `klytron:deploy:clean_repo` | Purge the cached git mirror to force a clean clone |
| `deploy:cleanup` | Delete old releases (keeps `keep_releases`) |

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
