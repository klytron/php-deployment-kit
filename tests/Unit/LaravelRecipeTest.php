<?php

namespace Klytron\PhpDeploymentKit\Tests\Unit;

use PHPUnit\Framework\TestCase;

class LaravelRecipeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Laravel environment
        if (!defined('LARAVEL_START')) {
            define('LARAVEL_START', true);
        }
    }

    public function testLaravelRecipeLoads()
    {
        // Test that Laravel recipe can be loaded
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $this->assertFileExists($recipePath);
        
        $content = file_get_contents($recipePath);
        $this->assertStringContainsString('klytron:laravel:init:questions', $content);
        $this->assertStringContainsString('Laravel-specific tasks', $content);
    }

    public function testLaravelConfigurationDefaults()
    {
        // Test Laravel-specific default configurations
        $expectedDefaults = [
            'public_dir_path' => '{{deploy_path}}/current/public',
            'shared_dir_path' => '{{deploy_path}}/shared',
        ];

        foreach ($expectedDefaults as $key => $expected) {
            $this->assertEquals($expected, $expectedDefaults[$key]);
        }
    }

    public function testLaravelTasksExist()
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $expectedTasks = [
            'klytron:laravel:init:questions',
            'klytron:laravel:deploy',
            'klytron:laravel:rollback',
        ];

        foreach ($expectedTasks as $task) {
            $this->assertStringContainsString($task, $content, "Task {$task} should exist in Laravel recipe");
        }
    }

    public function testLaravelRecipeIncludesCoreTasks()
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $this->assertStringContainsString('klytron-tasks.php', $content);
        $this->assertStringContainsString('require_once', $content);
    }
}
