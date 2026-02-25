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

use Klytron\PhpDeploymentKit\Services\DeploymentMetricsService;

/**
 * Deployment Metrics Display Task
 */
class DeploymentMetricsTask
{
    /**
     * Display deployment metrics summary
     */
    public static function displayMetrics(): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        info("📊 ===== DEPLOYMENT METRICS SUMMARY =====");

        try {
            $metrics = DeploymentMetricsService::getMetrics();
            
            if (empty($metrics)) {
                info("ℹ️ No metrics available for this deployment");
                return;
            }

            // Display deployment duration
            if (isset($metrics['deployment_duration'])) {
                $duration = $metrics['deployment_duration'];
                info("⏱️ Total Deployment Time: " . DeploymentMetricsService::formatDuration($duration));
            }

            // Display task results
            $taskMetrics = array_filter($metrics, fn($k) => str_starts_with($k, 'task_'), ARRAY_FILTER_USE_KEY);
            if (!empty($taskMetrics)) {
                info("📋 Task Results:");
                foreach ($taskMetrics as $key => $task) {
                    $taskName = str_replace('task_', '', $key);
                    $status = $task['success'] ? '✅ SUCCESS' : '❌ FAILED';
                    $duration = isset($task['duration']) ? " ({$task['duration']}s)" : '';
                    info("  {$taskName}: {$status}{$duration}");
                    if (!$task['success'] && !empty($task['error'])) {
                        info("    Error: {$task['error']}");
                    }
                }
            }

            // Display asset mapping results
            if (isset($metrics['asset_mapping'])) {
                $assetMapping = $metrics['asset_mapping'];
                info("🔗 Asset Mapping:");
                info("  Total Assets Mapped: {$assetMapping['total_assets']}");
                info("  Mapped At: {$assetMapping['mapped_at']}");
            }

            // Display font verification results
            if (isset($metrics['font_verification'])) {
                $fontVerification = $metrics['font_verification'];
                info("🔤 Font Verification:");
                info("  Total Fonts: {$fontVerification['total_fonts']}");
                info("  Accessible: {$fontVerification['accessible_fonts']}");
                info("  Problematic: {$fontVerification['problematic_fonts']}");
            }

            // Display sitemap results
            if (isset($metrics['sitemap'])) {
                $sitemap = $metrics['sitemap'];
                info("🗺️ Sitemap Generation:");
                info("  Generated: " . ($sitemap['generated'] ? 'Yes' : 'No'));
                info("  Size: " . self::formatBytes($sitemap['size'] ?? 0));
                if (isset($sitemap['generated_at'])) {
                    info("  Generated At: {$sitemap['generated_at']}");
                }
            }

            // Display sitemap verification results
            if (isset($metrics['sitemap_verification'])) {
                $sitemapVerification = $metrics['sitemap_verification'];
                info("🔍 Sitemap Verification:");
                info("  Total Sitemaps: {$sitemapVerification['total_sitemaps']}");
                info("  Verified At: {$sitemapVerification['verified_at']}");
            }

            // Display sitemap accessibility results
            if (isset($metrics['sitemap_accessibility'])) {
                $sitemapAccessibility = $metrics['sitemap_accessibility'];
                info("🌐 Sitemap Accessibility:");
                info("  Total Checked: {$sitemapAccessibility['total_sitemaps']}");
                info("  Accessible: {$sitemapAccessibility['accessible_sitemaps']}");
                info("  Checked At: {$sitemapAccessibility['checked_at']}");
            }

            // Display image optimization results
            if (isset($metrics['image_optimization'])) {
                $imageOptimization = $metrics['image_optimization'];
                info("🖼️ Image Optimization:");
                info("  Original Size: " . self::formatBytes($imageOptimization['original_size']));
                info("  Optimized Size: " . self::formatBytes($imageOptimization['optimized_size']));
                info("  Space Saved: " . self::formatBytes($imageOptimization['savings_bytes']));
                info("  Savings: {$imageOptimization['savings_percent']}%");
                info("  Optimized At: {$imageOptimization['optimized_at']}");
            }

            info("========================================");

        } catch (Exception $e) {
            error("❌ Failed to display metrics: " . $e->getMessage());
        }
    }

    /**
     * Export metrics to file for analysis
     */
    public static function exportMetrics(string $filePath = null): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        try {
            $metrics = DeploymentMetricsService::getMetrics();
            
            if (empty($metrics)) {
                info("ℹ️ No metrics available to export");
                return;
            }

            $exportPath = $filePath ?: get('deploy_path', '/tmp') . '/deployment_metrics_export.json';
            
            if (!function_exists('Deployer\run')) {
                return;
            }

            $json = json_encode($metrics, JSON_PRETTY_PRINT);
            run("echo '{$json}' > {$exportPath}");
            
            info("📄 Metrics exported to: {$exportPath}");

        } catch (Exception $e) {
            error("❌ Failed to export metrics: " . $e->getMessage());
        }
    }

    /**
     * Load and display previous deployment metrics
     */
    public static function compareWithPrevious(): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        try {
            $currentMetrics = DeploymentMetricsService::getMetrics();
            $previousMetrics = DeploymentMetricsService::loadMetrics();

            if (empty($currentMetrics) || empty($previousMetrics)) {
                info("ℹ️ Cannot compare - missing current or previous metrics");
                return;
            }

            info("📊 ===== DEPLOYMENT COMPARISON =====");

            // Compare deployment duration
            if (isset($currentMetrics['deployment_duration']) && isset($previousMetrics['deployment_duration'])) {
                $current = $currentMetrics['deployment_duration'];
                $previous = $previousMetrics['deployment_duration'];
                $change = $current - $previous;
                $percentChange = round(($change / $previous) * 100, 2);
                
                if ($change > 0) {
                    info("⏱️ Deployment Time: +{$change}s ({$percentChange}% slower than previous)");
                } elseif ($change < 0) {
                    info("⏱️ Deployment Time: " . abs($change) . "s ({$percentChange}% faster than previous)");
                } else {
                    info("⏱️ Deployment Time: Same as previous ({$current}s)");
                }
            }

            // Compare task success rates
            $currentTasks = array_filter($currentMetrics, fn($k) => str_starts_with($k, 'task_'), ARRAY_FILTER_USE_KEY);
            $previousTasks = array_filter($previousMetrics, fn($k) => str_starts_with($k, 'task_'), ARRAY_FILTER_USE_KEY);

            if (!empty($currentTasks) && !empty($previousTasks)) {
                $currentSuccess = count(array_filter($currentTasks, fn($t) => $t['success'] ?? false));
                $previousSuccess = count(array_filter($previousTasks, fn($t) => $t['success'] ?? false));
                
                $currentTotal = count($currentTasks);
                $previousTotal = count($previousTasks);
                
                $currentRate = round(($currentSuccess / $currentTotal) * 100, 1);
                $previousRate = round(($previousSuccess / $previousTotal) * 100, 1);
                
                info("📈 Success Rate: {$currentRate}% (Previous: {$previousRate}%)");
            }

            info("========================================");

        } catch (Exception $e) {
            error("❌ Failed to compare metrics: " . $e->getMessage());
        }
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
}
