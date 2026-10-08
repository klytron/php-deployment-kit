# 🔧 Troubleshooting Guide

[← Back to Documentation](README.md)

## 📋 Table of Contents

- [Quick Diagnosis](#quick-diagnosis)
- [Common Issues](#common-issues)
- [Deployment Issues](#deployment-issues)
- [Database Issues](#database-issues)
- [SSH Issues](#ssh-issues)
- [Permission Issues](#permission-issues)
- [Configuration Issues](#configuration-issues)
- [Performance Issues](#performance-issues)
- [Debugging Tools](#debugging-tools)
- [Getting Help](#getting-help)

## 🚨 Quick Diagnosis

### Health Check Commands

```bash
# CI smoke test: validates config + task graph, no SSH needed
vendor/bin/dep klytron:plan

# Local validation subtasks (deploy path, domain, env files, placeholders, user)
vendor/bin/dep klytron:validate:basic

# Release a stuck deploy lock, then inspect / roll back
vendor/bin/dep deploy:unlock
vendor/bin/dep rollback
```

### Emergency Commands

```bash
# Deploy with verbose output
vendor/bin/dep deploy -v

# Show what would run (plan resolves the full task graph)
vendor/bin/dep klytron:plan

# Rollback to previous version
vendor/bin/dep rollback
```

## ❌ Common Issues

### Issue: gallery/asset images 403 while everything else returns 200

**Symptoms:** after a root-run deploy, image URLs (e.g. storage/image
symlinks inside a real-directory `public_html`) answer 403 while pages, CSS
and JS are fine.

**Cause:** the symlinks were created as root, but the vhost uses Apache
`SymLinksIfOwnerMatch`, which only follows links owned by the target's owner
(the app `http_user`). Two subtleties learned the hard way: plain `chown`
*follows* symlinks (fixes targets, leaves link inodes root-owned — always use
`chown -h` for the inodes), and the served dir itself is often a symlink
(kit `server_symlink` points `public_html` at the release `public/`), so the
fix must follow the command-line path (`find -H`) rather than skipping
symlinked dirs. Decision: since `http_user`/`http_group` are always defined
by the consuming project's `deploy.php`, the finalize permissions step
re-points both the release-tree link inodes and the served-dir child links at
them on every deploy — no behavior change otherwise.

**Verify:**
```bash
ls -la <public_html>/ | grep -E 'images|storage'   # links must show the app user, not root
curl -s -o /dev/null -w "%{http_code}\n" https://<domain>/images/<known-file>
```

### Issue: "Permission denied" errors

**Symptoms:**
- SSH connection fails
- File operations fail
- Deployment stops with permission errors

**Solutions:**

```bash
# Check SSH key permissions
chmod 600 ~/.ssh/id_rsa
chmod 644 ~/.ssh/id_rsa.pub

# Then validate the whole plan locally (no SSH needed)
vendor/bin/dep klytron:plan
```

**Configuration fix:**
```php
// In deploy.php
klytron_configure_host('your-server.com', [
    'remote_user' => 'deploy', // Use dedicated deploy user
    'http_user' => 'www-data',
    'writable_mode' => 'chmod',
    'writable_use_sudo' => true,
]);
```

### Issue: Database connection fails

**Symptoms:**
- Migration errors
- Backup failures
- Database-related deployment stops

**Solutions:**

```bash
# Validate the deploy plan (checks env files, placeholders, remote user)
vendor/bin/dep klytron:plan
vendor/bin/dep klytron:validate:basic

# Run the database step on its own
vendor/bin/dep klytron:laravel:deploy:database:complete
```

**Configuration fix:**
```php
// Verify database configuration (actual signature:
klytron_configure_database(string $type, array $config))
klytron_configure_database('mysql', [
    'import_path' => 'database/live-db-exports',
    'supports_migrations' => true,
    'supports_seeders' => false,
]);
```

### Issue: Composer dependencies fail

**Symptoms:**
- Composer install fails
- Package conflicts
- Memory limit errors

**Solutions:**

```bash
# Re-run the vendor step on its own (vendor/ is hardlink-cached
# from the previous release by klytron:cache:vendor)
vendor/bin/dep deploy:vendors

# Deploy with verbose output to see the failing command
vendor/bin/dep deploy -v
```

**Configuration fix:**
```php
// In deploy.php — Composer runs via deploy:vendors; tune Deployer natively
set('default_timeout', 1800);
```

## 🚀 Deployment Issues

### Issue: Deployment hangs or times out

**Symptoms:**
- Deployment process stops responding
- Long-running operations
- Timeout errors

**Solutions:**

```bash
# Validate the plan without SSH (resolves the full task graph)
vendor/bin/dep klytron:plan

# Deploy in stages using real flow tasks
vendor/bin/dep klytron:validate:basic
vendor/bin/dep deploy:release
vendor/bin/dep deploy:update_code
vendor/bin/dep deploy:vendors
```

**Configuration fix:**
```php
// Increase timeouts natively
set('default_timeout', 1800);
```

### Issue: Asset compilation fails

**Symptoms:**
- Vite/Mix build errors
- Asset compilation timeouts
- Missing compiled assets

**Solutions:**

```bash
# Re-run the real build tasks
vendor/bin/dep klytron:node:build
vendor/bin/dep klytron:laravel:node:vite:build
vendor/bin/dep klytron:laravel:node:mix:build
```

**Configuration fix:**
```php
// Configure asset compilation with real keys (see configuration-reference.md)
set('supports_vite', true);
set('vite_build_command', 'npm run build');
set('npm_cache_dir', '{{deploy_path}}/.npm-cache');
```

### Issue: Zero-downtime deployment fails

**Symptoms:**
- Application downtime during deployment
- Users see errors during deployment
- Maintenance mode issues

**Solutions:**

```bash
# Live HTTP verification after deploy (real task, runs in the flow)
vendor/bin/dep klytron:deploy:health_check

# Tune it natively
# set('health_check_timeout', 15);
# set('health_check_expected_code', 200);
```

**Configuration fix:**
```php
// Health check is klytron:deploy:health_check (URL from
// application_public_url / application_public_domain)
set('health_check_timeout', 15);
set('health_check_expected_code', 200);
```

## 🗄️ Database Issues

### Issue: Migration fails

**Symptoms:**
- Database migration errors
- Schema conflicts
- Migration rollback issues

**Solutions:**

```bash
# Check migration status on the server, then run the real migrate task
vendor/bin/dep klytron:laravel:deploy:db:migrate

# SQLite failures print the still-Pending rows from migrate:status
# and fail loud (continue-defaults-to-no) — fix schema, redeploy
```

**Configuration fix:**
```php
// Database behavior is driven by the init answers / auto_* keys
// (see configuration-reference.md "Unattended CI")
set('auto_database_operation', 'migrations');
```

### Issue: Database backup fails

**Symptoms:**
- Backup creation errors
- Insufficient disk space
- Permission denied for backup

**Solutions:**

```bash
# Create a pre-deploy backup with the real task
vendor/bin/dep klytron:deploy:backup:create
```

**Configuration fix:**
```php
// No klytron_configure_backup() helper exists — use runtime flags
set('shouldBackupBeforeDeployment', true);
set('shouldBackupDatabase', true);
```

## 🔐 SSH Issues

### Issue: SSH connection fails

**Symptoms:**
- "Connection refused" errors
- SSH key authentication fails
- Host key verification fails

**Solutions:**

```bash
# Test SSH outside Deployer first
ssh -T deploy@your-server.com

# Generate a new SSH key (local shell, not a dep task)
ssh-keygen -t ed25519 -C "deploy@your-server.com"
```

**Configuration fix:**
```php
// Configure SSH settings
klytron_configure_host('your-server.com', [
    'ssh_type' => 'native',
    'ssh_arguments' => [
        '-o', 'StrictHostKeyChecking=no',
        '-o', 'UserKnownHostsFile=/dev/null',
        '-o', 'ConnectTimeout=30',
    ],
    'ssh_multiplexing' => true,
]);
```

### Issue: SSH key not found

**Symptoms:**
- "No such file or directory" for SSH key
- Authentication fails
- Key path errors

**Solutions:**

```bash
# Check the local SSH key, then validate the plan
ls -l ~/.ssh/id_rsa ~/.ssh/id_ed25519
vendor/bin/dep klytron:plan
```

**Configuration fix:**
```php
// Specify SSH key path
klytron_configure_host('your-server.com', [
    'ssh_key_file' => '/path/to/private/key',
    'ssh_public_key_file' => '/path/to/public/key',
]);
```

## 📁 Permission Issues

### Issue: File permission errors

**Symptoms:**
- "Permission denied" for file operations
- Cannot write to directories
- Ownership issues

**Solutions:**

```bash
# Re-run the real permission sweep (single remote script, setgid dirs,
# node_modules/.git/.npm-cache pruned, symlink inodes via chown -h)
vendor/bin/dep klytron:deploy:access_permissions
vendor/bin/dep klytron:deploy:laravel:access_permissions
```

**Configuration fix:**
```php
// Configure permissions
klytron_configure_host('your-server.com', [
    'writable_mode' => 'chmod',
    'writable_use_sudo' => true,
    'writable_dirs' => [
        'storage',
        'bootstrap/cache',
        'public/uploads',
    ],
    'http_user' => 'www-data',
    'http_group' => 'www-data',
]);
```

### Issue: Directory not writable

**Symptoms:**
- Cannot create directories
- Cannot write to storage
- Cache directory issues

**Solutions:**

```bash
# Re-run the real permission sweeps (see above)
vendor/bin/dep klytron:deploy:access_permissions
```

## ⚙️ Configuration Issues

### Issue: Configuration validation fails

**Symptoms:**
- Configuration errors
- Missing required settings
- Invalid configuration values

**Solutions:**

```bash
# Validate configuration (real tasks — no config:validate/config:check/config:generate exist)
vendor/bin/dep klytron:plan
vendor/bin/dep klytron:validate:basic
```

**Configuration fix:**
```php
// Ensure all required settings
klytron_configure_app('my-app', 'git@github.com:user/my-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_configure_host('your-server.com', [
    'remote_user' => 'deploy',
    'http_user' => 'www-data',
]);
```

### Issue: Environment-specific configuration

**Symptoms:**
- Wrong environment loaded
- Configuration conflicts
- Environment variables not set

**Solutions:**

```bash
# Validate the resolved environment locally (no env:* tasks exist)
vendor/bin/dep klytron:plan
vendor/bin/dep klytron:validate:env_files
```

**Configuration fix:**
```php
// No klytron_configure_environment() helper exists — environments are just
// .env files selected with native set():
set('env_file_local', '.env.production');
set('env_file_remote', '.env');
// set('env_file_local', false); // projects with no env file at all
```

## ⚡ Performance Issues

### Issue: Slow deployment (deployments taking 10–15+ minutes)

**Symptoms:**
- Deployments take 15–20 minutes instead of seconds.
- Long stalls during `deploy:vendors` (Composer install).
- Long stalls during `klytron:laravel:node:vite:build` (npm install).
- Long stalls during `klytron:deploy:access_permissions`.
- Long stalls during `deploy:update_code`.

**Root Causes & Solutions (Built into v1.1.0+):**

1. **Destructive Git Cache Purging**:
   - *Previous cause*: Custom scripts or older tasks executing `rm -rf {{deploy_path}}/.dep/repo` on every deploy, forcing Git to re-clone the entire repository over the internet.
   - *Fix*: `klytron:deploy:fix_repo` only clears stale `index.lock` files. Never delete `.dep/repo` unless explicitly troubleshooting via `klytron:deploy:clean_repo`.

2. **Uncached Composer Dependencies**:
   - *Previous cause*: Every release performed a full download of all vendor packages.
   - *Fix*: The kit's `klytron:cache:vendor` task automatically reuses `vendor/` from the previous release via hardlinks (`cp -al`). Composer only downloads diffs, dropping install times from 3 minutes to ~2 seconds.

3. **Uncached npm / Vite Builds**:
   - *Previous cause*: Every release ran `npm install` on the remote server across hundreds of megabytes of node modules.
   - *Fix*: `klytron:laravel:node:vite:build` reuses `node_modules` from the previous release via hardlinks and hashes `package-lock.json`. If unchanged, it skips `npm install` completely and runs Vite directly.

4. **Sequential `sudo` Subshell Explosion / permissions hang**:
   - *Previous cause*: Permissions commands using `find ... -exec sudo chmod g+s {} \;`, or recursive chown across `node_modules`, or multi-SSH Laravel storage/cache steps that stall and leave `deploy.lock`.
   - *Fix (v1.1.5)*: `klytron:deploy:access_permissions` batches updates with setgid octal modes, **prunes `node_modules`/`.git`/`.npm-cache`**, and runs as one remote script. `klytron:deploy:laravel:access_permissions` uses timeouts and a single SSH round-trip.

5. **Stale CSS/JS hashes after deploy (Virtualmin)**:
   - *Cause*: `systemctl reload php-fpm` does not clear OPcache for Virtualmin `php-cgi` FCGI workers.
   - *Fix (v1.1.5)*: `klytron:fpm:reload` also runs `klytron:opcache:reset` against the live domain SAPI.

6. **Pruning Unnecessary Tasks**:
   - Remove database migration tasks if your project has `'database' => 'none'`.
   - Remove sitemap or image optimization tasks if your project does not generate them.

**Expected Speed with v1.1.0:**
- Routine code updates: **30–60 seconds** total deployment time.
- Releases with asset changes: **1.5–2.5 minutes**.

### Issue: Memory issues

**Symptoms:**
- Out of memory errors
- Composer fails
- Process killed

**Solutions:**

```bash
# Deploy with verbose output to find the slow step
vendor/bin/dep deploy -v

# Clear Laravel caches with the real task
vendor/bin/dep klytron:laravel:deploy:cache:clear:all
```

## 🛠️ Debugging Tools

### Debug Commands

```bash
# Deploy with verbose output
vendor/bin/dep deploy -v

# Show deployment info (real tasks)
vendor/bin/dep klytron:deploy:info
vendor/bin/dep klytron:laravel:deploy:info

# Validate the plan without SSH
vendor/bin/dep klytron:plan
vendor/bin/dep klytron:validate:basic
```

### Health Monitoring

```bash
# Live HTTP verification (real task, also runs in the flow)
vendor/bin/dep klytron:deploy:health_check
```

### Log Analysis

```bash
# Deployer has no logs:* tasks — re-run with verbose output instead
vendor/bin/dep deploy -v
vendor/bin/dep klytron:plan
```

## 🆘 Getting Help

### Self-Help Resources

- [Documentation](README.md) - Complete guides and references
- [Configuration Reference](configuration-reference.md) - All configuration options
- [Task Reference](task-reference.md) - Available commands and tasks
- [Examples](examples/) - Real-world usage examples

### Community Support

- [GitHub Issues](https://github.com/klytron/php-deployment-kit/issues) - Report bugs and request features
- [GitHub Discussions](https://github.com/klytron/php-deployment-kit/discussions) - Ask questions and share solutions
- [Stack Overflow](https://stackoverflow.com/questions/tagged/klytron-php-deployment-kit) - Community Q&A

### Professional Support

- **Author**: Michael K. Laweh (klytron)
- **Website**: [https://www.klytron.com](https://www.klytron.com)
- **Email**: hi@klytron.com

### Reporting Issues

When reporting issues, please include:

1. **Klytron Deployer version**: `vendor/bin/dep --version`
2. **PHP version**: `php --version`
3. **Operating system**: `uname -a`
4. **Error message**: Complete error output
5. **Configuration**: Relevant parts of your `deploy.php`
6. **Steps to reproduce**: Detailed steps to reproduce the issue

### Example Issue Report

```markdown
## Issue Description
Brief description of the problem

## Environment
- Klytron Deployer: 1.0.0
- PHP: 8.1.0
- OS: Ubuntu 20.04
- Server: CentOS 7

## Steps to Reproduce
1. Run `vendor/bin/dep deploy`
2. Error occurs at step X
3. See error message below

## Error Message
```
Complete error output here
```

## Configuration
```php
// Relevant configuration from deploy.php
```

## Expected Behavior
What should happen instead
```

---

**💡 Pro Tip**: Always test your deployment configuration in a staging environment before deploying to production.

**🔍 Debug Mode**: Use `--debug` flag with any command to get detailed output for troubleshooting.
