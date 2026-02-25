<?php

namespace Klytron\PhpDeploymentKit\Tasks;

use Exception;
use Klytron\PhpDeploymentKit\Exceptions\AssetMappingException;
use Klytron\PhpDeploymentKit\Services\RetryService;
use Klytron\PhpDeploymentKit\Services\DeploymentMetricsService;
use function Deployer\info;
use function Deployer\warning;
use function Deployer\error;
use function Deployer\run;
use function Deployer\test;
use function Deployer\parse;

/**
 * Asset Mapping Task
 * 
 * Maps asset files for database compatibility when using Vite/Mix
 * This ensures old asset references in database continue to work
 */
class AssetMappingTask
{
    /**
     * Map asset files for database compatibility
     */
    public static function mapAssets(): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        DeploymentMetricsService::startTimer('asset_mapping');
        info("🔗 Mapping asset files for database compatibility...");

        $manifestPath = '{{release_path}}/public/build/manifest.json';
        $jsPath = '{{release_path}}/public/build/assets';

        try {
            // Check if manifest exists with retry
            $manifestExists = RetryService::execute(function() {
                $manifestPath = '{{release_path}}/public/build/manifest.json';
                return test("[ -f \"$manifestPath\" ]");
            });

            if (!$manifestExists) {
                throw AssetMappingException::manifestNotFound($manifestPath);
            }

            // Read manifest with error handling
            $manifestContent = RetryService::execute(function() use ($manifestPath) {
                if (!function_exists('Deployer\run')) {
                    return null;
                }
                return run("cat \"$manifestPath\"");
            });

            if (!$manifestContent) {
                throw AssetMappingException::manifestNotFound($manifestPath);
            }

            $manifest = json_decode($manifestContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new AssetMappingException(
                    "Invalid JSON in manifest: " . json_last_error_msg(),
                    ['manifest_path' => $manifestPath, 'json_error' => json_last_error_msg()]
                );
            }

            $mappedAssets = [];
            foreach ($manifest as $original => $mapped) {
                if (is_array($mapped) && isset($mapped['file'])) {
                    $sourceFile = parse("{{release_path}}/public/build/{$mapped['file']}");
                    $targetFile = parse("{{release_path}}/public/{$original}");
                    
                    // Create symbolic link with retry
                    $success = RetryService::execute(function() use ($sourceFile, $targetFile) {
                        if (!function_exists('Deployer\run')) {
                            return false;
                        }
                        
                        // Check if source file exists
                        if (!test("[ -f \"$sourceFile\" ]")) {
                            warning("  ⚠️ Source file does not exist: $sourceFile");
                            return false;
                        }
                        
                        // Ensure target directory exists
                        $targetDir = dirname($targetFile);
                        run("mkdir -p \"$targetDir\"");
                        
                        // Create symbolic link
                        return run("ln -sf \"$sourceFile\" \"$targetFile\"");
                    });
                    
                    if ($success) {
                        $mappedAssets[] = [
                            'original' => $original,
                            'mapped' => $mapped['file'],
                            'source' => $sourceFile,
                            'target' => $targetFile
                        ];
                        info("  ✅ Mapped: {$original} → {$mapped['file']}");
                    } else {
                        warning("  ⚠️ Failed to map asset: {$original} → {$mapped['file']} (non-critical, continuing)");
                    }
                }
            }

            DeploymentMetricsService::recordAssetMapping($mappedAssets);

            // Get current app.js file from manifest
            $appEntry = $manifest['resources/js/app.js'] ?? null;
            if ($appEntry && isset($appEntry['file'])) {
                $currentAppFile = basename($appEntry['file']);
                info("📄 Current app file: $currentAppFile");

                // List of known old app file names that might be referenced in database
                $knownOldAppFiles = [
                    'app-f0ea6ee4.js',  // Common hash pattern
                    'app-e3b0c442.js',  // Common fallback
                    'app.js'            // Generic fallback
                ];

                $mappedFiles = count($mappedAssets);
                foreach ($knownOldAppFiles as $oldFile) {
                    if ($oldFile !== $currentAppFile && !test("[ -f \"$jsPath/$oldFile\" ]")) {
                        run("cp $jsPath/$currentAppFile $jsPath/$oldFile");
                        info("✅ Mapped $oldFile → $currentAppFile");
                        $mappedFiles++;
                    }
                }

                // Also map other common asset files if they don't exist
                $commonAssets = [
                    'ui-640054ec.js',
                    'icons-2c580926.js',
                    'vendor-752fb3b7.js',
                    'ProductListing-320cfd70.js',
                    'ShowMore-b40375ae.js'
                ];

                foreach ($commonAssets as $assetFile) {
                    if (test("[ -f \"$jsPath/$assetFile\" ]")) {
                        info("✅ Asset exists: $assetFile");
                    } else {
                        // Try to find similar file in manifest and map it
                        foreach ($manifest as $entry) {
                            if (isset($entry['file']) && strpos($entry['file'], 'assets/') === 0) {
                                $manifestFile = basename($entry['file']);
                                if (strpos($manifestFile, explode('-', $assetFile)[0]) === 0) {
                                    run("cp $jsPath/$manifestFile $jsPath/$assetFile");
                                    info("✅ Mapped $assetFile → $manifestFile");
                                    $mappedFiles++;
                                    break;
                                }
                            }
                        }
                    }
                }

                if ($mappedFiles > 0) {
                    info("🎉 Successfully mapped $mappedFiles asset files for database compatibility");
                } else {
                    info("ℹ️ No asset mapping needed - all files already exist");
                }
            }

            DeploymentMetricsService::recordTaskResult('asset_mapping', true, DeploymentMetricsService::endTimer('asset_mapping'));
            info("✅ Asset mapping completed successfully. Mapped " . count($mappedAssets) . " manifest assets.");

        } catch (AssetMappingException $e) {
            DeploymentMetricsService::recordTaskResult('asset_mapping', false, DeploymentMetricsService::endTimer('asset_mapping'), $e->getMessage());
            error("❌ Asset mapping failed: " . $e->getMessage());
            if ($e->getSuggestion()) {
                info("💡 Suggestion: " . $e->getSuggestion());
            }
            throw $e;
        } catch (Exception $e) {
            DeploymentMetricsService::recordTaskResult('asset_mapping', false, DeploymentMetricsService::endTimer('asset_mapping'), $e->getMessage());
            error("❌ Unexpected error during asset mapping: " . $e->getMessage());
            throw new AssetMappingException(
                "Unexpected error during asset mapping: " . $e->getMessage(),
                ['original_error' => $e->getMessage(), 'trace' => $e->getTraceAsString()],
                "Check deployment logs and try again"
            );
        }
    }

    /**
     * Clean up problematic .htaccess files that cause 500 errors
     */
    public static function cleanupHtaccess(): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        info("🧹 Cleaning up problematic .htaccess files...");

        $jsHtaccessPath = '{{release_path}}/public/build/assets/.htaccess';

        if (test("[ -f \"$jsHtaccessPath\" ]")) {
            run("rm \"$jsHtaccessPath\"");
            info("✅ Removed problematic .htaccess from JavaScript directory");
        } else {
            info("ℹ️ No problematic .htaccess files found");
        }

        info("🧹 .htaccess cleanup completed");
    }

    /**
     * Verify font files exist and are accessible
     */
    public static function verifyFonts(): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        DeploymentMetricsService::startTimer('font_verification');
        info("🔤 Verifying font files...");

        try {
            $fontDirs = [
                '{{release_path}}/public/fonts',
                '{{release_path}}/public/assets/fonts',
                '{{release_path}}/public/build/assets/fonts'
            ];

            $totalFonts = 0;
            $accessibleFonts = 0;
            $problematicFonts = [];

            foreach ($fontDirs as $fontDir) {
                if (!test("[ -d \"$fontDir\" ]")) {
                    continue;
                }

                // Find font files with retry
                $fontFiles = RetryService::execute(function() use ($fontDir) {
                    if (!function_exists('Deployer\run')) {
                        return [];
                    }
                    $result = run("find \"$fontDir\" -type f \( -name '*.woff2' -o -name '*.woff' -o -name '*.ttf' -o -name '*.otf' -o -name '*.eot' \) 2>/dev/null");
                    return empty(trim($result)) ? [] : explode("\n", trim($result));
                });

                foreach ($fontFiles as $fontFile) {
                    $totalFonts++;
                    
                    // Check accessibility with retry
                    $isAccessible = RetryService::execute(function() use ($fontFile) {
                        if (!function_exists('Deployer\test')) {
                            return false;
                        }
                        return test("[ -r \"$fontFile\" ]");
                    });

                    if ($isAccessible) {
                        $accessibleFonts++;
                        info("  ✅ Accessible: " . basename($fontFile));
                    } else {
                        $problematicFonts[] = $fontFile;
                        warning("  ❌ Not accessible: " . basename($fontFile));
                    }
                }
            }

            // Record metrics
            DeploymentMetricsService::recordMetric('font_verification', [
                'total_fonts' => $totalFonts,
                'accessible_fonts' => $accessibleFonts,
                'problematic_fonts' => count($problematicFonts),
                'verified_at' => date('Y-m-d H:i:s')
            ]);

            if (count($problematicFonts) > 0) {
                throw AssetMappingException::fontNotAccessible(implode(', ', array_map('basename', $problematicFonts)));
            }

            DeploymentMetricsService::recordTaskResult('font_verification', true, DeploymentMetricsService::endTimer('font_verification'));
            info("✅ Font verification completed. {$accessibleFonts}/{$totalFonts} fonts accessible.");

        } catch (AssetMappingException $e) {
            DeploymentMetricsService::recordTaskResult('font_verification', false, DeploymentMetricsService::endTimer('font_verification'), $e->getMessage());
            error("❌ Font verification failed: " . $e->getMessage());
            if ($e->getSuggestion()) {
                info("💡 Suggestion: " . $e->getSuggestion());
            }
            throw $e;
        } catch (Exception $e) {
            DeploymentMetricsService::recordTaskResult('font_verification', false, DeploymentMetricsService::endTimer('font_verification'), $e->getMessage());
            error("❌ Unexpected error during font verification: " . $e->getMessage());
            throw new AssetMappingException(
                "Unexpected error during font verification: " . $e->getMessage(),
                ['original_error' => $e->getMessage()],
                "Check file permissions and web server configuration"
            );
        }
    }

    /**
     * Debug font loading issues
     */
    public static function debugFonts(): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        info("🐛 Debugging font loading issues...");

        // Check if public_html symlink exists and points to correct location
        if (test('[ -L "{{application_public_html}}" ]')) {
            $symlinkTarget = run('readlink {{application_public_html}}');
            info("🔗 Public HTML symlink points to: $symlinkTarget");

            // Check if font files are accessible via symlink
            $fontPath = "{{application_public_html}}/build/assets/fonts/inter-v12-latin-regular.woff2";
            if (test("[ -f $fontPath ]")) {
                info("✅ Font file accessible via public HTML symlink");
            } else {
                warning("❌ Font file NOT accessible via public HTML symlink");
            }
        } else {
            warning("❌ Public HTML symlink does not exist");
        }

        // Check web server configuration
        info("🌐 Checking web server configuration...");

        // Test if .htaccess exists in public directory
        if (test('[ -f "{{application_public_html}}/.htaccess" ]')) {
            info("✅ .htaccess file exists in public directory");

            // Check if font MIME types are configured
            $mimeConfig = run('grep -c "font/woff" {{application_public_html}}/.htaccess || echo "0"');
            if ($mimeConfig > 0) {
                info("✅ Font MIME types configured in .htaccess");
            } else {
                warning("❌ Font MIME types NOT configured in .htaccess");
            }
        } else {
            warning("❌ .htaccess file missing in public directory");
        }

        info("🐛 Font debugging completed. Check output above for issues.");
    }
}
