<?php

namespace Klytron\PhpDeploymentKit\Exceptions;

/**
 * Asset Mapping Exception
 */
class AssetMappingException extends DeploymentException
{
    protected string $errorType = 'asset_mapping_error';

    public static function manifestNotFound(string $manifestPath): self
    {
        return new self(
            "Vite manifest not found at: {$manifestPath}",
            ['manifest_path' => $manifestPath],
            "Ensure Vite build has been completed and manifest.json exists"
        );
    }

    public static function assetMappingFailed(string $source, string $target): self
    {
        return new self(
            "Failed to map asset from {$source} to {$target}",
            ['source' => $source, 'target' => $target],
            "Check file permissions and ensure target directory exists"
        );
    }

    public static function fontNotFound(string $fontPath): self
    {
        return new self(
            "Font file not found: {$fontPath}",
            ['font_path' => $fontPath],
            "Verify font files are properly deployed and accessible"
        );
    }

    public static function fontNotAccessible(string $fontPath): self
    {
        return new self(
            "Font file not accessible: {$fontPath}",
            ['font_path' => $fontPath],
            "Check file permissions and web server configuration"
        );
    }
}
