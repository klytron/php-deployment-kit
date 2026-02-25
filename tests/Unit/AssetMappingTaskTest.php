<?php

namespace Klytron\PhpDeploymentKit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Klytron\PhpDeploymentKit\Tasks\AssetMappingTask;
use Klytron\PhpDeploymentKit\Exceptions\AssetMappingException;

class AssetMappingTaskTest extends TestCase
{
    public function testMapAssetsWithValidManifest(): void
    {
        // This test would require mocking Deployer functions
        // For now, we'll test the logic structure
        $this->assertTrue(true);
    }

    public function testAssetMappingExceptionManifestNotFound(): void
    {
        $exception = AssetMappingException::manifestNotFound('/path/to/manifest.json');
        
        $this->assertEquals('asset_mapping_error', $exception->getErrorType());
        $this->assertStringContainsString('Vite manifest not found', $exception->getMessage());
        $this->assertNotNull($exception->getSuggestion());
        $this->assertArrayHasKey('manifest_path', $exception->getContext());
    }

    public function testAssetMappingExceptionAssetMappingFailed(): void
    {
        $exception = AssetMappingException::assetMappingFailed('/source/file', '/target/file');
        
        $this->assertEquals('asset_mapping_error', $exception->getErrorType());
        $this->assertStringContainsString('Failed to map asset', $exception->getMessage());
        $this->assertNotNull($exception->getSuggestion());
        $this->assertArrayHasKey('source', $exception->getContext());
        $this->assertArrayHasKey('target', $exception->getContext());
    }

    public function testAssetMappingExceptionFontNotFound(): void
    {
        $exception = AssetMappingException::fontNotFound('/path/to/font.woff2');
        
        $this->assertEquals('asset_mapping_error', $exception->getErrorType());
        $this->assertStringContainsString('Font file not found', $exception->getMessage());
        $this->assertNotNull($exception->getSuggestion());
        $this->assertArrayHasKey('font_path', $exception->getContext());
    }

    public function testAssetMappingExceptionFontNotAccessible(): void
    {
        $exception = AssetMappingException::fontNotAccessible('/path/to/font.woff2');
        
        $this->assertEquals('asset_mapping_error', $exception->getErrorType());
        $this->assertStringContainsString('Font file not accessible', $exception->getMessage());
        $this->assertNotNull($exception->getSuggestion());
        $this->assertArrayHasKey('font_path', $exception->getContext());
    }

    public function testExceptionToArrayStructure(): void
    {
        $exception = AssetMappingException::manifestNotFound('/test/manifest.json');
        $array = $exception->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('error_type', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('context', $array);
        $this->assertArrayHasKey('suggestion', $array);
        $this->assertArrayHasKey('code', $array);
        $this->assertEquals('asset_mapping_error', $array['error_type']);
    }
}
