<?php

/**
 * PhpDeploymentKit - Laravel Recipe
 * 
 * Laravel-specific deployment tasks
 * 
 * @package Klytron\PhpDeploymentKit
 */

namespace Deployer;

require_once __DIR__ . '/klytron-laravel-recipe.php';

if (!has('public_dir_path')) {
    set('public_dir_path', '{{deploy_path}}/current/public');
}
if (!has('shared_dir_path')) {
    set('shared_dir_path', '{{deploy_path}}/shared');
}

task('klytron:laravel:artisan:migrate', function () {
    if (!get('shouldRunMigration', false)) {
        info("Skipping migrations");
        return;
    }
    
    info("Running migrations...");
    run('cd {{current_path}} && {{bin/php}} artisan migrate --force');
})->desc('Run Laravel migrations');

task('klytron:laravel:artisan:seed', function () {
    if (!get('shouldRunSeeder', false)) {
        info("Skipping seeders");
        return;
    }
    
    info("Running seeders...");
    run('cd {{current_path}} && {{bin/php}} artisan db:seed --force');
})->desc('Run Laravel seeders');

task('klytron:laravel:cache:clear', function () {
    if (!get('shouldClearAllCaches', true)) {
        info("Skipping cache clear");
        return;
    }
    
    info("Clearing caches...");
    run('cd {{current_path}} && {{bin/php}} artisan cache:clear');
    run('cd {{current_path}} && {{bin/php}} artisan config:clear');
    run('cd {{current_path}} && {{bin/php}} artisan route:clear');
    run('cd {{current_path}} && {{bin/php}} artisan view:clear');
})->desc('Clear Laravel caches');

task('klytron:laravel:storage:link', function () {
    if (!get('supports_storage_link', true)) {
        info("Storage link not supported");
        return;
    }
    
    info("Creating storage symlink...");
    run('cd {{current_path}} && {{bin/php}} artisan storage:link');
})->desc('Create storage symlink');

task('klytron:laravel:passport:install', function () {
    if (!get('shouldRunPassportInstall', false)) {
        info("Skipping Passport install");
        return;
    }
    
    if (!get('supports_passport', false)) {
        info("Passport not supported");
        return;
    }
    
    info("Installing Laravel Passport...");
    run('cd {{current_path}} && {{bin/php}} artisan passport:install --force');
})->desc('Install Laravel Passport');

task('klytron:laravel:deploy', function () {
    invoke('klytron:laravel:cache:clear');
    invoke('klytron:laravel:storage:link');
    
    if (get('shouldRunMigration', false)) {
        invoke('klytron:laravel:artisan:migrate');
    }
    
    if (get('shouldRunPassportInstall', false)) {
        invoke('klytron:laravel:passport:install');
    }
})->desc('Complete Laravel deployment tasks');

task('klytron:deploy:laravel:access_permissions', function () {
    $httpUser = get('http_user', 'www-data');
    
    run('chmod -R 0775 {{current_path}}/storage');
    run('chmod -R 0775 {{current_path}}/bootstrap/cache');
    run("chown -R $httpUser:{{current_path}}/storage");
    run("chown -R $httpUser:{{current_path}}/bootstrap/cache");
})->desc('Set Laravel-specific permissions');
