<?php

/**
 * Klytron Yii2 Deployment Recipe
 *
 * All Yii2-specific tasks and logic are defined here. This file is intended
 * to be included only for Yii2 projects.
 *
 * @package Klytron\Deployer\Yii2
 */

namespace Deployer;

// Load standard Yii2 recipe if available
if (file_exists('recipe/yii2-app-advanced.php')) {
    require 'recipe/yii2-app-advanced.php';
}

// Load core framework-agnostic tasks
require_once __DIR__ . '/../klytron-tasks.php';

///////////////////////////////////////////////////////////////////////////////
// YII2-SPECIFIC CONFIGURATION
///////////////////////////////////////////////////////////////////////////////

// Set Yii2-specific defaults
if (!has('public_dir_path')) {
    set('public_dir_path', '{{deploy_path}}/current/frontend/web');
}
if (!has('shared_dir_path')) {
    set('shared_dir_path', '{{deploy_path}}/shared');
}

///////////////////////////////////////////////////////////////////////////////
// YII2-SPECIFIC TASKS AND GROUPS
///////////////////////////////////////////////////////////////////////////////

/**
 * Display comprehensive Yii2 deployment information
 */
/**
 * Set environment file path
 */
function klytron_yii2_set_env_file(string $envFile): void
{
    set('env_file', $envFile);
}

/**
 * Set SQLite database file path
 */
function klytron_yii2_set_sqlite_file(string $sqliteFile): void
{
    set('sqlite_file', $sqliteFile);
}

task('klytron:yii2:deploy:info', function () {
    try {
        $hostname = currentHost()->getHostname();
        $release = get('release_name', 'Unknown');
        $stage = get('stage', 'production');

        info("🎯 ===== Yii2 Deployment Information =====");
        info("");
        info("📋 Basic Information:");
        info("   🔎 Application: " . get('application', 'No name configured'));
        info("   📦 Repository: " . get('repository', 'No repository configured'));
        info("   🌿 Branch: " . get('branch', 'main'));
        info("   📊 Stage: $stage");
        info("   🔢 Release: $release");
        info("");
        info("🖥️  Server Configuration:");
        info("   🚚 Server: " . get('hostname', 'No server configured'));
        info("   🖥️  Host: $hostname");
        info("   📂 Deploy Path: " . get('deploy_path', 'not set'));
        info("   🔗 Public HTML: " . get('application_public_html', 'Not configured'));
        info("");
        info("🌐 Domain Information:");
        info("   🌐 Domain: " . get('application_public_domain', 'No domain configured'));
        info("   🔗 URL: " . get('application_public_url', 'No domain configured'));
        info("");
        info("⚙️  Deployment Settings:");
        info("   ⏱️  Timeout: " . get('default_timeout', 1800) . " seconds");
        info("   🔄 Releases Kept: " . get('keep_releases', 3));
        info("   🐘 PHP Version: " . get('php_version', 'system default'));
        info("");
        info("🎯 Yii2-Specific Configuration:");
        info("   📁 Public Directory: " . get('public_dir_path', 'frontend/web'));
        info("   📁 Shared Directory: " . get('shared_dir_path', 'shared'));
        info("   🗄️  Database Type: " . get('database_type', 'sqlite'));
        info("   📄 Environment File: " . get('env_file', '.env.production'));
        info("");
        info("================================");
    } catch (\Exception $e) {
        warning("⚠️  Some deployment information could not be retrieved");
        info("🔎 Application: " . get('application', 'Unknown'));
        info("📦 Repository: " . get('repository', 'Unknown'));
        info("🌿 Branch: " . get('branch', 'main'));
    }
})->desc('Display comprehensive Yii2 deployment information');

/**
 * Interactive configuration for Yii2 deployments with automation support
 *
 * Supports automated deployment configuration through these variables:
 * - auto_confirm_production: true/false/null (skip production confirmation)
 * - auto_deployment_type: 'update'/'fresh'/null (deployment type)
 * - auto_upload_env: true/false/null (upload .env file)
 * - auto_database_operation: 'migrations'/'import'/'both'/'none'/null (database operation)
 * - auto_clear_caches: true/false/null (clear caches)
 * - auto_confirm_settings: true/false/null (skip final confirmation)
 */
task('klytron:yii2:deploy:configure:interactive', function () {
    info("🎯 ===== Yii2 Deployment Configuration =====");
    info("");
    
    // Get application information for context
    $appName = get('application', 'Unknown Application');
    $appEnv = get('stage', 'production');
    
    info("🚀 Application: $appName");
    info("🌍 Environment: $appEnv");
    info("");
    
    // 1. Confirm deployment to production (with automation support)
    $autoConfirm = get('auto_confirm_production', null);
    if ($autoConfirm !== null) {
        $deployToProduction = $autoConfirm;
        info("🤖 Auto-confirming production deployment: " . ($deployToProduction ? 'YES' : 'NO'));
    } else {
        $deployToProduction = askConfirmation("Are you sure you want to deploy to production?", true);
    }
    
    if (!$deployToProduction) {
        error("❌ Deployment cancelled by user.");
        throw new \RuntimeException("Deployment cancelled by user.");
    }
    
    // 2. Check if current deployment exists and ask about deployment type (with automation support)
    $currentRelease = null;
    try {
        $currentRelease = run('cd {{deploy_path}} && ls -la current 2>/dev/null | grep -o "[0-9]*" | head -1 || echo ""');
    } catch (\Exception $e) {
        // No current release exists
    }
    
    $autoDeploymentType = get('auto_deployment_type', null);
    if ($autoDeploymentType !== null) {
        $deploymentType = $autoDeploymentType;
        info("🤖 Auto-selecting deployment type: $deploymentType");
    } else {
        if (!empty($currentRelease)) {
            info("🔄 Current release detected: $currentRelease");
            $deploymentType = askChoice("Select deployment type:", [
                'update' => 'Update existing deployment',
                'fresh' => 'Fresh installation (will replace current)'
            ], 'update');
        } else {
            info("🆆 No current release found - this is a fresh installation");
            $deploymentType = 'fresh';
        }
    }
    
    $isUpdate = ($deploymentType === 'update');
    
    // 3. Configure based on deployment type
    if ($isUpdate) {
        //===========================================
        // UPDATE DEPLOYMENT
        //===========================================
        info("📋 Deployment Type: UPDATE EXISTING DEPLOYMENT");
        info("🔄 Updating existing installation");
        info("");
        
        // For updates, we can be more conservative (with automation support)
        $autoUploadEnv = get('auto_upload_env', null);
        if ($autoUploadEnv !== null) {
            $shouldUploadEnvFile = $autoUploadEnv;
            info("🤖 Auto-setting upload env file: " . ($shouldUploadEnvFile ? 'YES' : 'NO'));
        } else {
            $shouldUploadEnvFile = askConfirmation("Upload .env.production file?", true);
        }
        
        $autoDatabaseOp = get('auto_database_operation', null);
        if ($autoDatabaseOp !== null) {
            $databaseOperation = $autoDatabaseOp;
            info("🤖 Auto-selecting database operation: $databaseOperation");
        } else {
            $databaseOperation = askChoice("Select database operation:", [
                'migrations' => 'Run migrations only',
                'import' => 'Import database from SQL file',
                'both' => 'Run migrations AND import database',
                'none' => 'Skip database operations'
            ], 'migrations');
        }
        
        $autoClearCaches = get('auto_clear_caches', null);
        if ($autoClearCaches !== null) {
            $shouldClearCaches = $autoClearCaches;
            info("🤖 Auto-setting clear caches: " . ($shouldClearCaches ? 'YES' : 'NO'));
        } else {
            $shouldClearCaches = askConfirmation("Clear application caches after deployment?", true);
        }
        
    } else {
        //===========================================
        // FRESH INSTALLATION
        //===========================================
        info("📋 Deployment Type: FRESH PROJECT INSTALLATION");
        info("🆆 No current release found - this is a fresh installation");
        info("");
        
        // For fresh installations, we need more configuration (with automation support)
        $envFileLocal = get('env_file_local', '.env.production');
        
        $autoUploadEnv = get('auto_upload_env', null);
        if ($autoUploadEnv !== null) {
            $shouldUploadEnvFile = $autoUploadEnv;
            info("🤖 Auto-setting upload env file: " . ($shouldUploadEnvFile ? 'YES' : 'NO'));
        } else {
            $shouldUploadEnvFile = askConfirmation("Upload $envFileLocal file?", true);
        }
        
        $autoDatabaseOp = get('auto_database_operation', null);
        if ($autoDatabaseOp !== null) {
            $databaseOperation = $autoDatabaseOp;
            info("🤖 Auto-selecting database operation: $databaseOperation");
        } else {
            $databaseOperation = askChoice("Select database operation:", [
                'migrations' => 'Run migrations only',
                'import' => 'Import database from SQL file',
                'both' => 'Run migrations AND import database',
                'none' => 'Skip database operations'
            ], 'import');
        }
        
        $autoClearCaches = get('auto_clear_caches', null);
        if ($autoClearCaches !== null) {
            $shouldClearCaches = $autoClearCaches;
            info("🤖 Auto-setting clear caches: " . ($shouldClearCaches ? 'YES' : 'NO'));
        } else {
            $shouldClearCaches = askConfirmation("Clear application caches after deployment?", true);
        }
    }
    
    // 4. Final confirmation (with automation support)
    $autoConfirmSettings = get('auto_confirm_settings', null);
    if ($autoConfirmSettings !== null) {
        $confirmSettings = $autoConfirmSettings;
        info("🤖 Auto-confirming final settings: " . ($confirmSettings ? 'YES' : 'NO'));
    } else {
        info("");
        info("📋 Final Deployment Settings:");
        info("   🚀 Application: $appName");
        info("   🌍 Environment: $appEnv");
        info("   📋 Type: " . ($isUpdate ? 'UPDATE' : 'FRESH INSTALLATION'));
        info("   📄 Upload .env: " . ($shouldUploadEnvFile ? 'YES' : 'NO'));
        info("   🗄️  Database: $databaseOperation");
        info("   🧹 Clear Caches: " . ($shouldClearCaches ? 'YES' : 'NO'));
        info("");
        
        $confirmSettings = askConfirmation("Proceed with these settings?", true);
    }
    
    if (!$confirmSettings) {
        error("❌ Deployment cancelled by user.");
        throw new \RuntimeException("Deployment cancelled by user.");
    }
    
    // Store configuration for use in deployment tasks
    set('deployment_type', $deploymentType);
    set('should_upload_env', $shouldUploadEnvFile);
    set('database_operation', $databaseOperation);
    set('should_clear_caches', $shouldClearCaches);
    
    info("");
    info("✅ Configuration complete - proceeding with deployment");
})->desc('Interactive Yii2 deployment configuration with automation support');

/**
 * Configure Yii2 application with comprehensive settings
 */
function klytron_configure_yii2_app(string $name, string $repository, array $config = []): void
{
    // Set basic application configuration
    set('application', $name);
    set('repository', $repository);
    
    // Set default values with overrides
    $defaults = [
        'deploy_path_parent' => 'DEPLOY_PATH_PARENT_NOT_SET_MANDATORY_CONFIG', //use defined value in deploy.php file of project, this is a mandatory config item
        'php_version' => '7.4',
        'database_type' => 'sqlite',
        'env_file' => '.env.production',
        'public_dir_path' => 'frontend/web',
        'shared_dir_path' => 'shared',
        'keep_releases' => 3,
        'default_timeout' => 1800,
    ];
    
    foreach ($defaults as $key => $value) {
        // Prioritize custom config over defaults
        if (isset($config[$key])) {
            set($key, $config[$key]);
        } elseif (!has($key)) {
            set($key, $value);
        }
    }
    
    // Log the deploy path being used
    // Log the deploy path being used (Context-safe and gated to avoid noise)
    $deployPathParent = get('deploy_path_parent');
    // Avoid Deployer\info() before a Context exists; probe with currentHost()
    $hasContext = false;
    try { currentHost(); $hasContext = true; } catch (\Throwable $___e) { $hasContext = false; }
    if (!$hasContext) {
        if (!get('klytron_logged_deploy_parent', false)) {
            if (function_exists('klytron_safe_log')) {
                klytron_safe_log("📂 Deploy path parent: $deployPathParent");
            }
            set('klytron_logged_deploy_parent', true);
        }
    } else {
        // removed import-time info() to avoid Context errors
        set('klytron_logged_deploy_parent', true);
    }
    
    // Defer deploy_path resolution; last-write-wins and Context-safe
    set('deploy_path', function () {
        $parent = get('deploy_path_parent');
        $app = get('application');
        return rtrim($parent, '/') . '/' . ltrim($app, '/');
    });
        
        // Set shared files and directories
    set('shared_files', [
        'common/config/main-local.php',
        'common/config/params-local.php',
        'frontend/config/main-local.php',
        'frontend/config/params-local.php',
        'backend/config/main-local.php',
        'backend/config/params-local.php',
        'console/config/main-local.php',
        'console/config/params-local.php',
        '{{env_file}}',
    ]);

    set('shared_dirs', [
        'common/runtime',
        'frontend/runtime',
        'frontend/web/assets',
        'backend/runtime',
        'backend/web/assets',
        'console/runtime',
        'common/web/uploads',
    ]);

    set('writable_dirs', [
        'common/runtime',
        'frontend/runtime',
        'frontend/web/assets',
        'backend/runtime',
        'backend/web/assets',
        'console/runtime',
        'common/web/uploads',
    ]);

}

task('klytron:yii2:set_permissions', function () {
    info("🔐 Setting Yii2 file permissions...");
    
    $httpUser = get('http_user', 'www-data');
    $httpGroup = get('http_group', 'www-data');
    
    // Set runtime directories permissions
    run("chmod -R 775 {{release_path}}/common/runtime");
    run("chmod -R 775 {{release_path}}/frontend/runtime");
    run("chmod -R 775 {{release_path}}/backend/runtime");
    run("chmod -R 775 {{release_path}}/console/runtime");
    
    // Set web assets permissions
    run("chmod -R 775 {{release_path}}/frontend/web/assets");
    run("chmod -R 775 {{release_path}}/backend/web/assets");
    
    // Set ownership
    run("chown -R $httpUser:$httpGroup {{release_path}}/common/runtime");
    run("chown -R $httpUser:$httpGroup {{release_path}}/frontend/runtime");
    run("chown -R $httpUser:$httpGroup {{release_path}}/backend/runtime");
    run("chown -R $httpUser:$httpGroup {{release_path}}/console/runtime");
    run("chown -R $httpUser:$httpGroup {{release_path}}/frontend/web/assets");
    run("chown -R $httpUser:$httpGroup {{release_path}}/backend/web/assets");
    
    info("✅ Yii2 file permissions set");
})->desc('Set Yii2 file permissions');

task('klytron:yii2:maintenance_enable', function () {
    info("🔧 Enabling Yii2 maintenance mode...");
    
    // Create maintenance file
    run('cd {{release_path}} && touch frontend/web/maintenance');
    run('cd {{release_path}} && touch backend/web/maintenance');
    
    info("✅ Yii2 maintenance mode enabled");
})->desc('Enable Yii2 maintenance mode');

task('klytron:yii2:maintenance_disable', function () {
    info("🔧 Disabling Yii2 maintenance mode...");
    
    // Remove maintenance files
    run('cd {{release_path}} && rm -f frontend/web/maintenance');
    run('cd {{release_path}} && rm -f backend/web/maintenance');
    
    info("✅ Yii2 maintenance mode disabled");
})->desc('Disable Yii2 maintenance mode');

task('klytron:yii2:upload_env', function () {
    $envFile = get('env_file', '.env.production');
    
    // Check if file exists locally (not on server)
    if (file_exists($envFile)) {
        info("📄 Uploading environment file: $envFile (will be saved as .env)");
        upload($envFile, '{{release_path}}/.env');
        info("✅ Environment file uploaded as .env");
    } else {
        warning("⚠️  Environment file not found locally: $envFile");
    }
})->desc('Upload Yii2 environment file');

task('klytron:yii2:upload_sqlite', function () {
    $sqliteFile = get('sqlite_file', 'common/data/db/demo.sqlite');
    
    if (test("[ -f $sqliteFile ]")) {
        info("🗄️  Uploading SQLite database: $sqliteFile");
        upload($sqliteFile, '{{shared_dir_path}}/');
        info("✅ SQLite database uploaded to shared directory");
    } else {
        warning("⚠️  SQLite database not found: $sqliteFile");
    }
})->desc('Upload Yii2 SQLite database');

task('klytron:yii2:warmup_cache', function () {
    info("🔥 Warming up Yii2 cache...");
    
    try {
        // Use cache/flush-all instead of cache/warmup (which doesn't exist)
        run('cd {{release_path}} && {{bin/php}} yii cache/flush-all');
        info("✅ Yii2 cache cleared");
    } catch (\Exception $e) {
        warning("⚠️  Cache operation failed: " . $e->getMessage());
    }
})->desc('Clear Yii2 cache');

task('klytron:yii2:compile_assets', function () {
    info("🏗️  Compiling Yii2 assets...");
    
    // Check if asset compilation is supported
    if (test('[ -f {{release_path}}/package.json ]')) {
        info("📦 Installing Node.js dependencies...");
        run('cd {{release_path}} && npm ci --production');
        
        info("🏗️  Building assets...");
        run('cd {{release_path}} && npm run build');
        info("✅ Yii2 assets compiled");
    } else {
        info("⏭️  No package.json found, skipping asset compilation");
    }
})->desc('Compile Yii2 assets');

task('klytron:yii2:health_check', function () {
    info("🏥 Performing Yii2 health check...");
    
    try {
        // Check if application is accessible
        $response = run('curl -s -o /dev/null -w "%{http_code}" http://localhost/ || echo "000"');
        
        if ($response == '200') {
            info("✅ Yii2 application is healthy (HTTP 200)");
        } else {
            warning("⚠️  Yii2 application returned HTTP $response");
        }
    } catch (\Exception $e) {
        warning("⚠️  Health check failed: " . $e->getMessage());
    }
})->desc('Perform Yii2 health check');

task('klytron:yii2:create_backup', function () {
    info("💾 Creating Yii2 backup...");
    
    $backupDir = get('backup_dir', 'backups');
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = "{{deploy_path}}/$backupDir/backup_$timestamp.tar.gz";
    
    // Create backup directory if it doesn't exist
    run("mkdir -p {{deploy_path}}/$backupDir");
    
    // Create backup of current release
    if (test('[ -d {{deploy_path}}/current ]')) {
        run("cd {{deploy_path}} && tar -czf $backupFile current/");
        info("✅ Yii2 backup created: $backupFile");
    } else {
        info("⏭️  No current release to backup");
    }
})->desc('Create Yii2 backup');

task('klytron:yii2:rollback', function () {
    info("🔄 Rolling back Yii2 deployment...");
    
    $backupDir = get('backup_dir', 'backups');
    $backups = run("ls -t {{deploy_path}}/$backupDir/backup_*.tar.gz 2>/dev/null | head -1 || echo ''");
    
    if (!empty($backups)) {
        info("📦 Restoring from backup: $backups");
        run("cd {{deploy_path}} && tar -xzf $backups");
        info("✅ Yii2 rollback completed");
    } else {
        warning("⚠️  No backup found for rollback");
    }
})->desc('Rollback Yii2 deployment');

task('klytron:yii2:clear_cache', function () {
    info("🧹 Clearing Yii2 cache...");
    
    run('cd {{release_path}} && {{bin/php}} yii cache/flush-all');
    run('cd {{release_path}} && {{bin/php}} yii cache/clear');
    
    info("✅ Yii2 cache cleared");
})->desc('Clear Yii2 cache');

task('klytron:yii2:copy_console', function () {
    info("📋 Copying Yii2 console file...");
    
    // Copy yii console file from environments/prod/yii to root
    if (test("[ -f {{release_path}}/environments/prod/yii ]")) {
        run('cp {{release_path}}/environments/prod/yii {{release_path}}/yii');
        run('chmod +x {{release_path}}/yii');
        info("✅ Yii2 console file copied and made executable");
    } else {
        warning("⚠️  Yii2 console file not found at environments/prod/yii");
    }
})->desc('Copy Yii2 console file to root directory');

task('klytron:yii2:migrate', function () {
    $databaseOperation = get('auto_database_operation');
    
    // Skip migration if auto_database_operation is not set to 'migrations'
    if ($databaseOperation !== 'migrations') {
        info("⏭️  Skipping database migrations (auto_database_operation: $databaseOperation)");
        return;
    }
    
    $databaseType = get('database_type', 'sqlite');
    info("🔄 Running Yii2 database migrations for $databaseType...");
    
    try {
        // Configure database based on type
        if ($databaseType === 'sqlite') {
            // Ensure SQLite database directory exists and is writable
            $sqliteFile = get('sqlite_file', 'common/data/db/database.sqlite');
            $sqliteDir = dirname($sqliteFile);
            
            // Create directory with proper permissions in release directory
            run("mkdir -p {{release_path}}/$sqliteDir");
            run("chmod 775 {{release_path}}/$sqliteDir");
            
            // Create SQLite file if it doesn't exist in release directory
            run("if [ ! -f {{release_path}}/$sqliteFile ]; then touch {{release_path}}/$sqliteFile; fi");
            run("chmod 664 {{release_path}}/$sqliteFile");
            
            // Set proper ownership
            $httpUser = get('http_user', 'www-data');
            $httpGroup = get('http_group', 'www-data');
            run("chown $httpUser:$httpGroup {{release_path}}/$sqliteFile");
            run("chown $httpUser:$httpGroup {{release_path}}/$sqliteDir");
            
            info("📁 SQLite database file prepared in release directory: $sqliteFile");
        }
        
        run('cd {{release_path}} && {{bin/php}} yii migrate --interactive=0');
        info("✅ Yii2 database migrations completed");
    } catch (\Exception $e) {
        error("❌ Yii2 database migration failed: " . $e->getMessage());
        throw new \RuntimeException("Deployment cancelled due to migration failure.");
    }
})->desc('Run Yii2 database migrations (conditional based on auto_database_operation)');

task('klytron:yii2:backup_sqlite', function () {
    $sqliteFile = get('sqlite_file', 'common/data/db/demo.sqlite');
    
    if (test("[ -f {{release_path}}/$sqliteFile ]")) {
        info("💾 Creating SQLite backup...");
        
        $backupDir = get('backup_dir', 'backups');
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = "{{deploy_path}}/$backupDir/sqlite_backup_$timestamp.sqlite";
        
        // Create backup directory if it doesn't exist
        run("mkdir -p {{deploy_path}}/$backupDir");
        
        // Copy SQLite file from release directory
        run("cp {{release_path}}/$sqliteFile $backupFile");
        info("✅ SQLite backup created: $backupFile");
    } else {
        info("⏭️  No SQLite database to backup");
    }
})->desc('Backup Yii2 SQLite database');

///////////////////////////////////////////////////////////////////////////////
// YII2 DEPLOYMENT FLOWS
///////////////////////////////////////////////////////////////////////////////

/**
 * Standard Yii2 deployment flow
 */
function klytron_yii2_deploy_flow(): array
{
    return [
        // Pre-deployment
        'klytron:deploy:start_timer',
        'klytron:validate:deploy_path_parent',  // Validate deploy_path_parent before proceeding
        'klytron:yii2:deploy:info',
        'klytron:yii2:deploy:configure:interactive',
        'deploy:unlock',
        'deploy:info',
        'deploy:setup',
        'deploy:lock',
        'deploy:release',
        'klytron:deploy:fix_repo',
        'deploy:update_code',
        
        // Environment and configuration
        'klytron:yii2:upload_env',
        'deploy:vendors',
        'deploy:shared',
        'deploy:writable',
        
        // Asset compilation
        'klytron:yii2:compile_assets',
        
        // Application-specific steps
        'klytron:yii2:maintenance_enable',
        'klytron:yii2:copy_console',
        'klytron:yii2:migrate',
        'deploy:symlink',
        'klytron:yii2:optimize',
        'klytron:yii2:create_symlinks',
        'klytron:yii2:upload_sqlite',
        'klytron:yii2:set_permissions',
        'klytron:yii2:warmup_cache',
        'klytron:yii2:maintenance_disable',
        
        // Post-deployment
        'deploy:unlock',
        'deploy:cleanup',
        'klytron:yii2:health_check',
    ];
}

/**
 * Minimal Yii2 deployment flow (for quick deployments)
 */
function klytron_yii2_deploy_flow_minimal(): array
{
    return [
        'klytron:deploy:start_timer',
        'klytron:validate:deploy_path_parent',  // Validate deploy_path_parent before proceeding
        'klytron:yii2:deploy:info',
        'deploy:unlock',
        'deploy:info',
        'deploy:setup',
        'deploy:lock',
        'deploy:release',
        'klytron:deploy:fix_repo',
        'deploy:update_code',
        'klytron:yii2:upload_env',
        'deploy:vendors',
        'deploy:shared',
        'deploy:writable',
        'klytron:yii2:maintenance_enable',
        'klytron:yii2:copy_console',
        'klytron:yii2:migrate',
        'deploy:symlink',
        'klytron:yii2:optimize',
        'klytron:yii2:create_symlinks',
        'klytron:yii2:set_permissions',
        'klytron:yii2:maintenance_disable',
        'deploy:unlock',
        'deploy:cleanup',
        'klytron:yii2:health_check',
    ];
}

/**
 * Yii2 deployment flow with backup
 */
function klytron_yii2_deploy_flow_with_backup(): array
{
    return [
        // Pre-deployment with backup
        'klytron:yii2:create_backup',
        'klytron:deploy:start_timer',
        'klytron:validate:deploy_path_parent',  // Validate deploy_path_parent before proceeding
        'klytron:yii2:deploy:info',
        'klytron:yii2:deploy:configure:interactive',
        'deploy:unlock',
        'deploy:info',
        'deploy:setup',
        'deploy:lock',
        'deploy:release',
        'klytron:deploy:fix_repo',
        'deploy:update_code',
        
        // Environment and configuration
        'klytron:yii2:upload_env',
        'deploy:vendors',
        'deploy:shared',
        'deploy:writable',
        
        // Asset compilation
        'klytron:yii2:compile_assets',
        
        // Application-specific steps
        'klytron:yii2:maintenance_enable',
        'klytron:yii2:copy_console',
        'klytron:yii2:migrate',
        'deploy:symlink',
        'klytron:yii2:optimize',
        'klytron:yii2:create_symlinks',
        'klytron:yii2:upload_sqlite',
        'klytron:yii2:set_permissions',
        'klytron:yii2:warmup_cache',
        'klytron:yii2:maintenance_disable',
        
        // Post-deployment
        'deploy:unlock',
        'deploy:cleanup',
        'klytron:yii2:health_check',
    ];
}

/**
 * Set application public HTML directory (frontend)
 */
function klytron_yii2_set_public_html(string $publicHtml): void
{
    set('application_public_html', $publicHtml);
}

/**
 * Set API public HTML directory (backend API)
 */
function klytron_yii2_set_api_public_html(string $publicHtml): void
{
    set('api_public_html', $publicHtml);
}

