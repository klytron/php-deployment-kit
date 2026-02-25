<?php

namespace Klytron\PhpDeploymentKit\Tasks;
use Exception;
use function Deployer\info;
use function Deployer\warning;
use function Deployer\error;
use function Deployer\run;
use function Deployer\test;
use function Deployer\get;
use function Deployer\set;

use Klytron\PhpDeploymentKit\Services\RetryService;
use Klytron\PhpDeploymentKit\Services\DeploymentMetricsService;
use Klytron\PhpDeploymentKit\Exceptions\NetworkException;

/**
 * Sitemap Generation Task
 * 
 * Generates sitemap for Laravel applications
 */
class SitemapTask
{
    /**
     * Generate sitemap for Laravel application
     */
    public static function generateSitemap(): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        DeploymentMetricsService::startTimer('sitemap_generation');
        info("🗺️ Generating sitemap...");

        try {
            // Check if sitemap command exists with retry
            $sitemapCommand = RetryService::execute(function() {
                $possibleCommands = [
                    'app:sitemap-generate',
                    'sitemap:generate',
                    'app:sitemap'
                ];

                foreach ($possibleCommands as $command) {
                    if (self::commandExists($command)) {
                        return $command;
                    }
                }
                return null;
            });

            if (!$sitemapCommand) {
                info("ℹ️ No sitemap command found. You may need to install a sitemap package.");
                info("💡 Consider installing: composer require spatie/laravel-sitemap");
                info("💡 Then run: php artisan vendor:publish --provider=\"Spatie\Sitemap\SitemapServiceProvider\"");
                info("💡 And add: php artisan sitemap:generate");
                
                DeploymentMetricsService::recordTaskResult('sitemap_generation', false, DeploymentMetricsService::endTimer('sitemap_generation'), 'No sitemap command available');
                return;
            }

            // Generate sitemap with retry
            $success = RetryService::execute(function() use ($sitemapCommand) {
                if (!function_exists('Deployer\run')) {
                    return false;
                }
                run('{{bin/php}} {{release_path}}/artisan ' . $sitemapCommand);
                return true;
            });

            if (!$success) {
                throw new NetworkException(
                    "Failed to generate sitemap using command: {$sitemapCommand}",
                    ['command' => $sitemapCommand],
                    "Check artisan command availability and permissions"
                );
            }

            // Verify sitemap was created and record metrics
            $sitemapPaths = [
                '{{release_path}}/public/sitemap.xml',
                '{{release_path}}/public/sitemap_index.xml'
            ];

            $sitemapInfo = null;
            foreach ($sitemapPaths as $sitemapPath) {
                if (test("[ -f $sitemapPath ]")) {
                    $size = RetryService::execute(function() use ($sitemapPath) {
                        if (!function_exists('Deployer\run')) {
                            return 0;
                        }
                        return (int)run("stat -c%s $sitemapPath");
                    });
                    
                    $sitemapInfo = [
                        'path' => basename($sitemapPath),
                        'size' => $size,
                        'generated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    DeploymentMetricsService::recordSitemapGeneration($sitemapPath, $size);
                    break;
                }
            }

            if (!$sitemapInfo) {
                throw new NetworkException(
                    "Sitemap generation completed but no sitemap file found",
                    ['checked_paths' => $sitemapPaths],
                    "Check artisan sitemap command output"
                );
            }

            DeploymentMetricsService::recordTaskResult('sitemap_generation', true, DeploymentMetricsService::endTimer('sitemap_generation'));
            info("✅ Sitemap generated successfully using command: $sitemapCommand");
            info("� Sitemap: {$sitemapInfo['path']} (" . self::formatBytes($sitemapInfo['size']) . ")");

        } catch (NetworkException $e) {
            DeploymentMetricsService::recordTaskResult('sitemap_generation', false, DeploymentMetricsService::endTimer('sitemap_generation'), $e->getMessage());
            error("❌ Sitemap generation failed: " . $e->getMessage());
            if ($e->getSuggestion()) {
                info("💡 Suggestion: " . $e->getSuggestion());
            }
            throw $e;
        } catch (Exception $e) {
            DeploymentMetricsService::recordTaskResult('sitemap_generation', false, DeploymentMetricsService::endTimer('sitemap_generation'), $e->getMessage());
            error("❌ Unexpected error during sitemap generation: " . $e->getMessage());
            throw new NetworkException(
                "Unexpected error during sitemap generation: " . $e->getMessage(),
                ['original_error' => $e->getMessage(), 'trace' => $e->getTraceAsString()],
                "Check artisan commands and configuration"
            );
        }
    }

    /**
     * Check if artisan command exists
     */
    private static function commandExists(string $command): bool
    {
        if (!function_exists('Deployer\run')) {
            return false;
        }

        $result = run('{{bin/php}} {{release_path}}/artisan list | grep -c "' . $command . '" || echo "0"');
        return trim($result) === '1';
    }

    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Verify sitemap was generated
     */
    public static function verifySitemap(): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        DeploymentMetricsService::startTimer('sitemap_verification');
        info("🔍 Verifying sitemap generation...");

        try {
            $sitemapPaths = [
                '{{release_path}}/public/sitemap.xml',
                '{{release_path}}/public/sitemap_index.xml',
                '{{application_public_html}}/sitemap.xml',
                '{{application_public_html}}/sitemap_index.xml'
            ];

            $foundSitemaps = [];
            $totalSize = 0;

            foreach ($sitemapPaths as $sitemapPath) {
                $exists = RetryService::execute(function() use ($sitemapPath) {
                    if (!function_exists('Deployer\test')) {
                        return false;
                    }
                    return test("[ -f $sitemapPath ]");
                });

                if ($exists) {
                    $size = RetryService::execute(function() use ($sitemapPath) {
                        if (!function_exists('Deployer\run')) {
                            return 0;
                        }
                        return (int)run("stat -c%s $sitemapPath");
                    });
                    
                    $foundSitemaps[] = [
                        'path' => basename($sitemapPath),
                        'size' => $size,
                        'accessible' => true
                    ];
                    $totalSize += $size;
                    
                    info("✅ Sitemap found: " . basename($sitemapPath) . " (" . self::formatBytes($size) . ")");
                }
            }

            if (empty($foundSitemaps)) {
                throw new NetworkException(
                    "No sitemap files found in public directory",
                    ['checked_paths' => $sitemapPaths],
                    "Check sitemap generation command output"
                );
            }

            // Record verification metrics
            DeploymentMetricsService::recordMetric('sitemap_verification', [
                'total_sitemaps' => count($foundSitemaps),
                'total_size' => $totalSize,
                'verified_at' => date('Y-m-d H:i:s'),
                'details' => $foundSitemaps
            ]);

            DeploymentMetricsService::recordTaskResult('sitemap_verification', true, DeploymentMetricsService::endTimer('sitemap_verification'));
            info("✅ Sitemap verification completed successfully. Found " . count($foundSitemaps) . " sitemap files.");

        } catch (NetworkException $e) {
            DeploymentMetricsService::recordTaskResult('sitemap_verification', false, DeploymentMetricsService::endTimer('sitemap_verification'), $e->getMessage());
            error("❌ Sitemap verification failed: " . $e->getMessage());
            if ($e->getSuggestion()) {
                info("💡 Suggestion: " . $e->getSuggestion());
            }
            throw $e;
        } catch (Exception $e) {
            DeploymentMetricsService::recordTaskResult('sitemap_verification', false, DeploymentMetricsService::endTimer('sitemap_verification'), $e->getMessage());
            error("❌ Unexpected error during sitemap verification: " . $e->getMessage());
            throw new NetworkException(
                "Unexpected error during sitemap verification: " . $e->getMessage(),
                ['original_error' => $e->getMessage(), 'trace' => $e->getTraceAsString()],
                "Check sitemap files and permissions"
            );
        }
    }

    /**
     * Check sitemap accessibility via HTTP
     */
    public static function checkSitemapAccessibility(): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        DeploymentMetricsService::startTimer('sitemap_accessibility');
        info("🌐 Checking sitemap accessibility...");

        try {
            $domain = get('domain', '');
            if (empty($domain)) {
                warning("⚠️ Domain not configured, skipping sitemap accessibility check");
                return;
            }

            $sitemapUrls = [
                "https://$domain/sitemap.xml",
                "https://$domain/sitemap_index.xml"
            ];

            $accessibleSitemaps = [];
            foreach ($sitemapUrls as $url) {
                // Check accessibility with retry
                $httpCode = RetryService::executeHttp(function() use ($url) {
                    $command = "curl -s -o /dev/null -w '%{http_code}' --max-time 10 '$url'";
                    if (!function_exists('Deployer\run')) {
                        return 0;
                    }
                    return (int)run($command);
                });

                if ($httpCode >= 200 && $httpCode < 400) {
                    $accessibleSitemaps[] = [
                        'url' => $url,
                        'status_code' => $httpCode,
                        'accessible' => true
                    ];
                    info("✅ Sitemap accessible: $url (HTTP $httpCode)");
                } else {
                    $accessibleSitemaps[] = [
                        'url' => $url,
                        'status_code' => $httpCode,
                        'accessible' => false
                    ];
                    warning("❌ Sitemap not accessible: $url (HTTP $httpCode)");
                }
            }

            // Record accessibility metrics
            DeploymentMetricsService::recordMetric('sitemap_accessibility', [
                'total_sitemaps' => count($sitemapUrls),
                'accessible_sitemaps' => count(array_filter($accessibleSitemaps, fn($s) => $s['accessible'])),
                'checked_at' => date('Y-m-d H:i:s'),
                'details' => $accessibleSitemaps
            ]);

            $accessibleCount = count(array_filter($accessibleSitemaps, fn($s) => $s['accessible']));
            DeploymentMetricsService::recordTaskResult('sitemap_accessibility', true, DeploymentMetricsService::endTimer('sitemap_accessibility'));
            info("✅ Sitemap accessibility check completed. {$accessibleCount}/" . count($sitemapUrls) . " sitemaps accessible.");

        } catch (NetworkException $e) {
            DeploymentMetricsService::recordTaskResult('sitemap_accessibility', false, DeploymentMetricsService::endTimer('sitemap_accessibility'), $e->getMessage());
            error("❌ Sitemap accessibility check failed: " . $e->getMessage());
            if ($e->getSuggestion()) {
                info("💡 Suggestion: " . $e->getSuggestion());
            }
            throw $e;
        } catch (Exception $e) {
            DeploymentMetricsService::recordTaskResult('sitemap_accessibility', false, DeploymentMetricsService::endTimer('sitemap_accessibility'), $e->getMessage());
            error("❌ Unexpected error during sitemap accessibility check: " . $e->getMessage());
            throw new NetworkException(
                "Unexpected error during sitemap accessibility check: " . $e->getMessage(),
                ['original_error' => $e->getMessage(), 'trace' => $e->getTraceAsString()],
                "Check domain configuration and network connectivity"
            );
        }
    }
}
