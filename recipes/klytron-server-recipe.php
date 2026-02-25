<?php

/**
 * Klytron Server Configuration Recipe
 * 
 * This recipe handles deployment of server configuration files like .htaccess, nginx configs, etc.
 * It's designed to work with any PHP project, not just Laravel.
 */

namespace Deployer;

/**
 * Deploy server configuration files
 * 
 * This task copies or symlinks server configuration files from the 'server' directory
 * to their appropriate locations in the deployment.
 * 
 * Configuration options:
 * - `server_config_files`: Array of configuration files to deploy. Each item should be an array with:
 *   - `source`: Source path relative to the project root (e.g., 'server/.htaccess.production')
 *   - `target`: Target path relative to the release path (e.g., 'public/.htaccess')
 *   - `symlink`: (optional) Whether to create a symlink instead of copying (default: false)
 *   - `mode`: (optional) File mode to set (e.g., 0644)
 */
/**
 * Deploy server configuration files
 * 
 * This task copies or symlinks server configuration files from the 'server' directory
 * to their appropriate locations in the deployment.
 * 
 * Configuration options:
 * - `server_config_files`: Array of configuration files to deploy. Each item should be an array with:
 *   - `source`: Source path relative to the project root (e.g., 'server/.htaccess.production')
 *   - `target`: Target path relative to the release path (e.g., 'public/.htaccess')
 *   - `symlink`: (optional) Whether to create a symlink instead of copying (default: false)
 *   - `mode`: (optional) File mode to set (e.g., 0644)
 *   - `owner`: (optional) File owner (default: http_user from host config)
 *   - `group`: (optional) File group (default: http_group from host config)
 *   - `overwrite`: (optional) Overwrite existing files (default: true)
 */
task('klytron:server:deploy:configs', function () {
    $defaultConfig = [
        [
            'source' => 'server/.htaccess.production',
            'target' => 'public/.htaccess',
            'symlink' => false,
            'mode' => 0644,
            'overwrite' => true
        ]
    ];
    
    $configs = get('server_config_files', $defaultConfig);
    $httpUser = get('http_user', 'www-data');
    $httpGroup = get('http_group', 'www-data');
    
    // Use release_or_current_path which handles both new deployments and updates
    $basePath = "{{release_or_current_path}}";

    foreach ($configs as $config) {
        $source = $config['source'];
        $target = rtrim($basePath, '/') . '/' . ltrim($config['target'], '/');
        $sourcePath = rtrim($basePath, '/') . '/' . ltrim($source, '/');
        $symlink = $config['symlink'] ?? false;
        $mode = $config['mode'] ?? 0644;
        $owner = $config['owner'] ?? $httpUser;
        $group = $config['group'] ?? $httpGroup;
        $overwrite = $config['overwrite'] ?? true;
        
        // Check if source file exists
        if (!test("[ -f '$sourcePath' ]")) {
            info("⏭️ Source file not found: $sourcePath, skipping...");
            continue;
        }

        // Skip if target exists and overwrite is false
        if (!$overwrite && test("[ -f '$target' ]")) {
            info("⏭️ Target file exists and overwrite is disabled: $target, skipping...");
            continue;
        }

        // Create target directory if it doesn't exist
        $targetDir = dirname($target);
        if (!test("[ -d '$targetDir' ]")) {
            run("mkdir -p $targetDir");
            run("chmod 755 $targetDir");
            run("chown $owner:$group $targetDir");
            info("📁 Created directory: $targetDir");
        }

        // Deploy the file
        if ($symlink) {
            // Remove existing target if it exists and is not a symlink
            if (test("[ -e '$target' ]") && !test("[ -L '$target' ]")) {
                run("rm -f $target");
            }
            // Create symlink
            run("ln -sf $sourcePath $target");
            info("🔗 Created symlink: $source → $target");
        } else {
            // Copy file with force to overwrite if exists
            run("cp -f $sourcePath $target");
            info("📄 Copied: $source → $target");
        }
        
        // Set file permissions and ownership
        run("chmod $mode $target");
        run("chown $owner:$group $target");
        
        // Ensure parent directory has correct permissions
        run("chmod 755 $targetDir");
        run("chown $owner:$group $targetDir");
    }
})->desc('Deploy server configuration files');

/**
 * Example task for deploying .htaccess file (backward compatibility)
 * @deprecated Use klytron:server:deploy:configs instead
 */
task('klytron:deploy:server:htaccess', function () {
    // For backward compatibility, we'll still support the old way
    $htaccessSource = get('htaccess_source', 'server/.htaccess.production');
    $htaccessTarget = '{{release_path}}/public/.htaccess';

    if (test("[ -f '{{release_path}}/$htaccessSource' ]")) {
        info("📄 Deploying production .htaccess file (legacy method)...");
        run("yes | cp -vf {{release_path}}/$htaccessSource $htaccessTarget");
        info("✅ Production .htaccess deployed successfully");
    } else {
        info("⏭️ No production .htaccess file found at $htaccessSource, skipping");
    }
})->desc('Legacy: Deploy production .htaccess file to public directory');

// Register the main task as an alias for backward compatibility
task('klytron:server:deploy', ['klytron:server:deploy:configs']);
