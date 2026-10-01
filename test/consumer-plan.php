#!/usr/bin/env php
<?php
/**
 * Standalone consumer plan verification runner.
 *
 * Can be executed in any consumer repository's CI pipeline to assert
 * that deploy.php is well-formed, task graphs resolve, and configuration
 * passes all pre-flight checks without SSH access.
 *
 * Usage in Consumer CI:
 *   php vendor/klytron/php-deployment-kit/test/consumer-plan.php [deploy.php]
 *   vendor/bin/klytron plan
 */

declare(strict_types=1);

$targetFile = $argv[1] ?? (getcwd() . '/deploy.php');

echo "🔍 [Klytron Plan Checker] Verifying deployment configuration...\n";
echo "📄 Target deploy file: {$targetFile}\n";

if (!file_exists($targetFile)) {
    fwrite(STDERR, "❌ Error: Target deploy file does not exist: {$targetFile}\n");
    exit(1);
}

// Set safe mock CI environment variables if not already set
if (getenv('DEPLOY_HOST') === false) {
    putenv('DEPLOY_HOST=ci.test.internal');
    $_ENV['DEPLOY_HOST'] = 'ci.test.internal';
}
if (getenv('DEPLOY_USER') === false) {
    putenv('DEPLOY_USER=deployer');
    $_ENV['DEPLOY_USER'] = 'deployer';
}
if (getenv('DEPLOY_BRANCH') === false) {
    putenv('DEPLOY_BRANCH=main');
    $_ENV['DEPLOY_BRANCH'] = 'main';
}

// Locate Deployer binary
$depCandidates = [
    getcwd() . '/vendor/bin/dep',
    __DIR__ . '/../vendor/bin/dep',
    __DIR__ . '/../../../../bin/dep',
];

$depBin = null;
foreach ($depCandidates as $candidate) {
    if (file_exists($candidate) && is_executable($candidate)) {
        $depBin = $candidate;
        break;
    }
}

if (!$depBin) {
    $depBin = 'dep';
}

// Deployer Importer requires files to end in .php or .yaml
$isTempDeploy = false;
$deployFileForDep = $targetFile;
if (!str_ends_with($targetFile, '.php') && !str_ends_with($targetFile, '.yaml')) {
    $deployFileForDep = getcwd() . '/.klytron_tmp_plan_' . uniqid() . '.php';
    copy($targetFile, $deployFileForDep);
    $isTempDeploy = true;
}

$cmd = sprintf(
    '%s klytron:plan -f %s 2>&1',
    escapeshellcmd($depBin),
    escapeshellarg($deployFileForDep)
);

// If .env.production does not exist in working directory, create temporary stub for CI plan check
$stubEnvCreated = false;
$stubEnvPath = getcwd() . '/.env.production';
if (!file_exists($stubEnvPath)) {
    echo "ℹ️  No .env.production found. Creating temporary mock environment file for CI validation...\n";
    file_put_contents($stubEnvPath, "APP_NAME=CiTest\nAPP_KEY=base64:stubkeyforplancheck1234567890=\n");
    $stubEnvCreated = true;
}

try {
    passthru($cmd, $exitCode);
} finally {
    if ($stubEnvCreated && file_exists($stubEnvPath)) {
        unlink($stubEnvPath);
    }
    if ($isTempDeploy && file_exists($deployFileForDep)) {
        unlink($deployFileForDep);
    }
}

if ($exitCode === 0) {
    echo "\n✅ [Klytron Plan Checker] Deployment plan & task graph verified successfully!\n";
    exit(0);
} else {
    fwrite(STDERR, "\n❌ [Klytron Plan Checker] Deployment plan check failed with exit code {$exitCode}.\n");
    exit($exitCode);
}
