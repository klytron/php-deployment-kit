<?php

namespace Klytron\PhpDeploymentKit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CommandsTest extends TestCase
{
    public function testStorageLinkCommandExists()
    {
        $commandPath = __DIR__ . '/../../src/Commands/KlytronStorageLinkCommand.php';
        $this->assertFileExists($commandPath);
        
        $content = file_get_contents($commandPath);
        $this->assertStringContainsString('klytron:storage:link-clean', $content);
        $this->assertStringContainsString('KlytronStorageLinkCommand', $content);
    }

    public function testStorageLinkCommandSignature()
    {
        $commandPath = __DIR__ . '/../../src/Commands/KlytronStorageLinkCommand.php';
        $content = file_get_contents($commandPath);
        
        $this->assertStringContainsString('protected $signature', $content);
        $this->assertStringContainsString('klytron:storage:link-clean', $content);
    }

    public function testAllCommandsHaveSignatures()
    {
        $commandsDir = __DIR__ . '/../../src/Commands/';
        $commandFiles = glob($commandsDir . '*.php');
        
        foreach ($commandFiles as $file) {
            $content = file_get_contents($file);
            $this->assertStringContainsString('protected $signature', $content, 
                "Command file " . basename($file) . " should have a signature");
        }
    }

    public function testAllCommandsHaveDescriptions()
    {
        $commandsDir = __DIR__ . '/../../src/Commands/';
        $commandFiles = glob($commandsDir . '*.php');
        
        foreach ($commandFiles as $file) {
            $content = file_get_contents($file);
            $this->assertStringContainsString('protected $description', $content, 
                "Command file " . basename($file) . " should have a description");
        }
    }

    public function testCommandClassesExist()
    {
        $expectedCommands = [
            'KlytronStorageLinkCommand',
            'KlytronDbSearchReplaceCommand',
            'KlytronFileEnCrypterCommand',
            'KlytronFileDeCrypterCommand',
            'KlytronSqliteSetterCommand',
        ];

        foreach ($expectedCommands as $command) {
            $filePath = __DIR__ . "/../../src/Commands/{$command}.php";
            $this->assertFileExists($filePath, "Command file for {$command} should exist");
        }
    }
}
