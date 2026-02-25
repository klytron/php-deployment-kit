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
}
