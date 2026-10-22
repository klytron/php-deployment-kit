<?php

namespace Klytron\PhpDeploymentKit\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ServerConfigRecipeTest extends TestCase
{
    public function testServerConfigRecipeFileExists(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-server-recipe.php';
        $this->assertFileExists($recipePath);

        $content = file_get_contents($recipePath);
        $this->assertStringContainsString('klytron:server:deploy:configs', $content);
        $this->assertStringContainsString('chmod $modeStr', $content);
        $this->assertStringContainsString("'mode' => '0644'", $content);
    }

    public function testOctalIntegerModeNormalization(): void
    {
        // In PHP, 0644 is integer 420. Without formatting, string interpolation yields '420'.
        $intMode = 0644;
        $this->assertSame(420, $intMode);

        $normalized = is_int($intMode) ? sprintf('%04o', $intMode) : (string) $intMode;
        $this->assertSame('0644', $normalized);

        // Test 0755 (int 493)
        $intDirMode = 0755;
        $normalizedDir = is_int($intDirMode) ? sprintf('%04o', $intDirMode) : (string) $intDirMode;
        $this->assertSame('0755', $normalizedDir);

        // Test 0600 (int 384)
        $intSecretMode = 0600;
        $normalizedSecret = is_int($intSecretMode) ? sprintf('%04o', $intSecretMode) : (string) $intSecretMode;
        $this->assertSame('0600', $normalizedSecret);
    }

    public function testStringModePreserved(): void
    {
        $stringMode = '0644';
        $normalized = is_int($stringMode) ? sprintf('%04o', $stringMode) : (string) $stringMode;
        $this->assertSame('0644', $normalized);

        $customStringMode = '755';
        $normalizedCustom = is_int($customStringMode) ? sprintf('%04o', $customStringMode) : (string) $customStringMode;
        $this->assertSame('755', $normalizedCustom);
    }
}
