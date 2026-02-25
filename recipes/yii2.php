<?php

/**
 * PhpDeploymentKit - Yii2 Recipe
 * 
 * Yii2-specific deployment tasks
 * 
 * @package Klytron\PhpDeploymentKit
 */

namespace Deployer;

require 'recipe/yii2-app-advanced.php';
require_once __DIR__ . '/../src/DeployerRecipe.php';

if (!has('public_dir_path')) {
    set('public_dir_path', '{{deploy_path}}/current/frontend/web');
}
if (!has('shared_dir_path')) {
    set('shared_dir_path', '{{deploy_path}}/shared');
}
