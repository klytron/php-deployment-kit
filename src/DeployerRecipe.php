<?php

/**
 * PhpDeploymentKit - Deployer Recipe
 * 
 * Main recipe file that provides helper functions for deployment configuration.
 *
 * @package Klytron\PhpDeploymentKit
 * @version 1.0.0
 * @author Michael K. Laweh (klytron) (https://www.klytron.com)
 * @license MIT
 */

namespace Deployer;

if (!function_exists('Deployer\set')) {
    return;
}

require_once __DIR__ . '/../deployment-kit-core.php';
