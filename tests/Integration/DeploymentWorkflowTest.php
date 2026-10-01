<?php

namespace Klytron\PhpDeploymentKit\Tests\Integration;

use PHPUnit\Framework\TestCase;

class DeploymentWorkflowTest extends TestCase
{
    public function testDeploymentKitCoreLoads()
    {
        $corePath = __DIR__ . '/../../deployment-kit-core.php';
        $this->assertFileExists($corePath);
        
        // Test that core file can be included without errors
        $content = file_get_contents($corePath);
        $this->assertStringContainsString('namespace Deployer', $content);
        $this->assertStringContainsString('KLYTRON DEPLOYER INITIALIZATION', $content);
    }

    public function testAllRecipesExist()
    {
        $recipesDir = __DIR__ . '/../../recipes/';
        $expectedRecipes = [
            'klytron-laravel-recipe.php',
            'klytron-yii2-recipe.php',
            'klytron-php-recipe.php',
            'klytron-server-recipe.php',
            'laravel.php',
            'yii2.php',
        ];

        foreach ($expectedRecipes as $recipe) {
            $recipePath = $recipesDir . $recipe;
            $this->assertFileExists($recipePath, "Recipe {$recipe} should exist");
            
            $content = file_get_contents($recipePath);
            $this->assertStringContainsString('<?php', $content, "Recipe {$recipe} should be valid PHP");
        }
    }

    public function testMainEntryPointsExist()
    {
        $expectedFiles = [
            'deployment-kit.php',
            'deployment-kit-core.php',
            'klytron-tasks.php',
        ];

        foreach ($expectedFiles as $file) {
            $filePath = __DIR__ . '/../../' . $file;
            $this->assertFileExists($filePath, "Main file {$file} should exist");
        }
    }

    public function testComposerConfiguration()
    {
        $composerPath = __DIR__ . '/../../composer.json';
        $this->assertFileExists($composerPath);
        
        $composer = json_decode(file_get_contents($composerPath), true);
        
        $this->assertEquals('klytron/php-deployment-kit', $composer['name']);
        $this->assertArrayHasKey('autoload', $composer);
        $this->assertArrayHasKey('psr-4', $composer['autoload']);
        $this->assertArrayHasKey('Klytron\\PhpDeploymentKit\\', $composer['autoload']['psr-4']);
    }

    public function testServiceProviderExists()
    {
        $providerPath = __DIR__ . '/../../src/Providers/PhpDeploymentKitServiceProvider.php';
        $this->assertFileExists($providerPath);
        
        $content = file_get_contents($providerPath);
        $this->assertStringContainsString('PhpDeploymentKitServiceProvider', $content);
        $this->assertStringContainsString('Illuminate\\Support\\ServiceProvider', $content);
    }

    public function testDocumentationStructure()
    {
        $docsDir = __DIR__ . '/../../docs/';
        $expectedDocs = [
            'README.md',
            'installation.md',
            'quick-start.md',
            'configuration-reference.md',
        ];

        foreach ($expectedDocs as $doc) {
            $docPath = $docsDir . $doc;
            $this->assertFileExists($docPath, "Documentation file {$doc} should exist");
        }
    }

    public function testExamplesAndTemplatesCanResolvePlan(): void
    {
        $rootDir = realpath(__DIR__ . '/../../');
        $depBin = $rootDir . '/vendor/bin/dep';

        if (!file_exists($depBin)) {
            $this->markTestSkipped('Deployer binary not found.');
        }

        // Ensure vendor/klytron/php-deployment-kit symlink exists for tests
        $vendorKlytronDir = $rootDir . '/vendor/klytron';
        if (!is_dir($vendorKlytronDir)) {
            mkdir($vendorKlytronDir, 0755, true);
        }
        $symlinkPath = $vendorKlytronDir . '/php-deployment-kit';
        if (!file_exists($symlinkPath)) {
            symlink($rootDir, $symlinkPath);
        }

        $targets = [
            $rootDir . '/examples/laravel-basic-example.php',
            $rootDir . '/examples/simple-php-example.php',
            $rootDir . '/templates/laravel-deploy.php.template',
            $rootDir . '/templates/simple-php.php.template',
            $rootDir . '/templates/deploy.php.template',
        ];

        foreach ($targets as $target) {
            $this->assertFileExists($target);

            // Create a temp deploy.php at repo root so __DIR__ . '/vendor/...' resolves
            $tmpDeploy = $rootDir . '/.tmp_test_deploy_' . uniqid() . '.php';
            file_put_contents($tmpDeploy, file_get_contents($target));

            try {
                $cmd = sprintf(
                    'DEPLOY_HOST=test.example.com %s list -f %s 2>&1',
                    escapeshellcmd($depBin),
                    escapeshellarg($tmpDeploy)
                );
                $output = [];
                $exitCode = 0;
                exec($cmd, $output, $exitCode);

                $this->assertSame(
                    0,
                    $exitCode,
                    sprintf("Failed to load %s via dep list:\n%s", basename($target), implode("\n", $output))
                );
            } finally {
                if (file_exists($tmpDeploy)) {
                    unlink($tmpDeploy);
                }
            }
        }
    }

    public function testGeneratedConfigResolvesPlan(): void
    {
        $rootDir = realpath(__DIR__ . '/../../');
        $depBin = $rootDir . '/vendor/bin/dep';

        if (!file_exists($depBin)) {
            $this->markTestSkipped('Deployer binary not found.');
        }

        $generator = new \Klytron\PhpDeploymentKit\Generators\DeployConfigGenerator();
        $generatedContent = $generator->generate('integration-test-app', 'laravel', [
            'domain' => 'integration.example.com',
            'repo' => 'git@github.com:klytron/test-repo.git',
        ]);

        $tmpDeploy = $rootDir . '/.tmp_test_generated_' . uniqid() . '.php';
        file_put_contents($tmpDeploy, $generatedContent);

        try {
            $cmd = sprintf(
                'DEPLOY_HOST=test.example.com %s list -f %s 2>&1',
                escapeshellcmd($depBin),
                escapeshellarg($tmpDeploy)
            );
            $output = [];
            $exitCode = 0;
            exec($cmd, $output, $exitCode);

            $this->assertSame(
                0,
                $exitCode,
                sprintf("Generated config failed dep list:\n%s", implode("\n", $output))
            );
        } finally {
            if (file_exists($tmpDeploy)) {
                unlink($tmpDeploy);
            }
        }
    }

    public function testConsumerPlanScript(): void
    {
        $rootDir = realpath(__DIR__ . '/../../');
        $consumerPlan = $rootDir . '/test/consumer-plan.php';
        $this->assertFileExists($consumerPlan);

        $template = $rootDir . '/templates/laravel-deploy.php.template';
        $tmpDeploy = $rootDir . '/.tmp_test_plan_' . uniqid() . '.php';
        file_put_contents($tmpDeploy, file_get_contents($template));

        try {
            $cmd = sprintf('php %s %s 2>&1', escapeshellarg($consumerPlan), escapeshellarg($tmpDeploy));
            $output = [];
            $exitCode = 0;
            exec($cmd, $output, $exitCode);

            $this->assertSame(0, $exitCode, "Consumer plan check failed:\n" . implode("\n", $output));
            $this->assertStringContainsString('Resolved Task Execution Sequence', implode("\n", $output));
        } finally {
            if (file_exists($tmpDeploy)) {
                unlink($tmpDeploy);
            }
        }
    }

    public function testTaskGraphResolutionDirect(): void
    {
        $rootDir = realpath(__DIR__ . '/../../');
        $depBin = $rootDir . '/vendor/bin/dep';

        $template = $rootDir . '/templates/laravel-deploy.php.template';
        $tmpDeploy = $rootDir . '/.tmp_test_graph_' . uniqid() . '.php';
        file_put_contents($tmpDeploy, file_get_contents($template));

        $tmpEnv = $rootDir . '/.env.production';
        $createdEnv = false;
        if (!file_exists($tmpEnv)) {
            file_put_contents($tmpEnv, "APP_NAME=GraphTest\nAPP_KEY=base64:stubkeyforplancheck1234567890=\n");
            $createdEnv = true;
        }

        try {
            $cmd = sprintf(
                'DEPLOY_HOST=ci.test.internal %s klytron:plan -f %s 2>&1',
                escapeshellcmd($depBin),
                escapeshellarg($tmpDeploy)
            );
            $output = [];
            $exitCode = 0;
            exec($cmd, $output, $exitCode);

            $outStr = implode("\n", $output);
            $this->assertSame(0, $exitCode, "Task graph resolution via klytron:plan failed:\n" . $outStr);
            $this->assertStringContainsString('Resolved Task Execution Sequence', $outStr);
            $this->assertStringContainsString('deploy:symlink', $outStr);
            $this->assertStringContainsString('klytron:laravel:filament:assets', $outStr);
            $this->assertStringContainsString('All plan configurations and task graph validated successfully', $outStr);
        } finally {
            if (file_exists($tmpDeploy)) {
                unlink($tmpDeploy);
            }
            if ($createdEnv && file_exists($tmpEnv)) {
                unlink($tmpEnv);
            }
        }
    }
}
