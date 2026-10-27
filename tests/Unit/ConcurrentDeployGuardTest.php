<?php

namespace Klytron\PhpDeploymentKit\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for the concurrent-deploy guard.
 *
 * Incident: two overlapping deploys shared the single staging symlink
 * {{deploy_path}}/release; the slower run failed later with a bare
 * "mv: cannot stat '<deploy_path>/release'" because the faster deploy's
 * atomic switch (deploy:symlink) had consumed the staging symlink.
 * Historically this was possible because every kit flow began with an
 * unconditional deploy:unlock that erased a live lock, and the failure hook
 * (after deploy:failed -> deploy:unlock) also cleared a foreign lock.
 *
 * Contract enforced here:
 *   1. Every deploy flow starts with klytron:deploy:smart_unlock (guarded
 *      unlock) instead of a raw deploy:unlock.
 *   2. Every deploy: symlink switch is preceded by
 *      klytron:deploy:verify_staging with a clear abort message.
 *   3. klytron-tasks.php keeps the stale-lock config and the owned-lock-only
 *      failure hook, and no raw after('deploy:failed', 'deploy:unlock').
 */
class ConcurrentDeployGuardTest extends TestCase
{
    private const FLOW_SOURCES = [
        'core (klytron_deploy_flow)' => 'deployment-kit-core.php',
        'laravel (klytron_laravel_deploy_flow)' => 'recipes/klytron-laravel-recipe.php',
        'laravel minimal (klytron_laravel_deploy_flow_minimal)' => 'recipes/klytron-laravel-recipe.php',
        'laravel api (klytron_laravel_deploy_flow_api)' => 'recipes/klytron-laravel-recipe.php',
        'php (klytron_php_deploy_flow)' => 'recipes/klytron-php-recipe.php',
        'php minimal (klytron_php_deploy_flow_minimal)' => 'recipes/klytron-php-recipe.php',
        'php complete task (klytron:php:deploy:complete)' => 'recipes/klytron-php-recipe.php',
        'php minimal task (klytron:php:deploy:minimal)' => 'recipes/klytron-php-recipe.php',
        'yii2 (klytron_yii2_deploy_flow)' => 'recipes/klytron-yii2-recipe.php',
        'yii2 minimal (klytron_yii2_deploy_flow_minimal)' => 'recipes/klytron-yii2-recipe.php',
        'yii2 with_backup (klytron_yii2_deploy_flow_with_backup)' => 'recipes/klytron-yii2-recipe.php',
    ];

    private const PACKAGE_ROOT = __DIR__ . '/../../';

    /**
     * @return array<string, string> label -> flow function body source
     */
    private function flowBodies(): array
    {
        $bodies = [];
        foreach (self::FLOW_SOURCES as $label => $file) {
            $content = file_get_contents(self::PACKAGE_ROOT . $file);
            $this->assertNotFalse($content, "Could not read $file");

            // Extract the array body that follows the flow function/task usage.
            $items = $this->extractFlowLists($content);
            $this->assertNotEmpty(
                $items,
                "No deploy flow lists found in $file (for $label)"
            );
            foreach ($items as $index => $body) {
                $bodies["$label #$index ($file)"] = $body;
            }
        }

        return $bodies;
    }

    /**
     * Grab every deploy-flow task list (task([...]) or function(): array [...])
     * whose contents include deploy:lock — those are deploy pipelines.
     *
     * @return array<int, string>
     */
    private function extractFlowLists(string $content): array
    {
        if (!preg_match_all("/\\[([^\\[\\]]*?)\\]/s", $content, $m)) {
            return [];
        }

        $flows = [];
        foreach ($m[1] as $list) {
            if (str_contains($list, 'deploy:lock') && str_contains($list, 'deploy:symlink')) {
                $flows[] = $list;
            }
        }

        return $flows;
    }

    public function testEveryFlowStartsWithGuardedUnlock(): void
    {
        foreach ($this->flowBodies() as $label => $body) {
            $smartScore = substr_count($body, "'klytron:deploy:smart_unlock'");
            $rawUnlocks = substr_count($body, "'deploy:unlock'");

            $this->assertSame(
                1,
                $smartScore,
                "$label must contain exactly one klytron:deploy:smart_unlock"
            );
            // Raw unlock only allowed AFTER the atomic switch (end-of-flow release).
            $switchPos = strpos($body, "'deploy:symlink'");
            $this->assertNotFalse($switchPos, "$label must contain deploy:symlink");
            $rawPos = strpos($body, "'deploy:unlock'");
            if ($rawPos !== false) {
                $this->assertGreaterThan(
                    $switchPos,
                    $rawPos,
                    "$label uses a raw deploy:unlock BEFORE the atomic switch (must be guarded smart_unlock)"
                );
            }
        }
    }

    public function testEverySymlinkSwitchIsPrecededByStagingGuard(): void
    {
        foreach ($this->flowBodies() as $label => $body) {
            $guarded = $body;
            // Guard must appear directly before the switch (allowing comments out).
            $guarded = str_replace("'klytron:deploy:verify_staging',", "'klytron:deploy:verify_staging'", $guarded);
            $count = substr_count($guarded, "'klytron:deploy:verify_staging'");
            $switches = substr_count($guarded, "'deploy:symlink'");
            $this->assertSame(
                $switches,
                $count,
                "$label: every deploy:symlink needs klytron:deploy:verify_staging directly before it"
            );
            $this->assertSame(1, $switches, "$label has exactly one symlink switch");
        }
    }

    public function testGuardTasksAreDefinedWithStaleLockConfig(): void
    {
        $content = file_get_contents(self::PACKAGE_ROOT . 'klytron-tasks.php');

        foreach (['klytron:deploy:smart_unlock', 'klytron:deploy:verify_staging', 'klytron:deploy:failed_unlock_guard'] as $task) {
            $this->assertStringContainsString(
                "task('$task'",
                $content,
                "Guard task $task must be defined in klytron-tasks.php"
            );
        }

        $this->assertStringContainsString(
            'klytron_stale_lock_minutes',
            $content,
            'Stale-lock threshold config must exist'
        );
        $this->assertStringContainsString(
            "stat -c %Y",
            $content,
            'Stale detection must be age-based'
        );
    }

    public function testFailureHookNeverUnconditionallyClearsTheLock(): void
    {
        $content = file_get_contents(self::PACKAGE_ROOT . 'klytron-tasks.php');

        // Comment-proof check: only a real registration (with spacing flexibility)
        // counts as a violation — prose like "unconditional after(...) hook" must not.
        $this->assertSame(
            0,
            (int) preg_match("/after\(\s*['\"]deploy:failed['\"]\s*,\s*['\"]deploy:unlock['\"]\s*\)/", $content),
            'Raw failure unlock must be replaced by the owned-lock-only guard'
        );
        $this->assertSame(
            1,
            (int) preg_match("/after\(\s*['\"]deploy:failed['\"]\s*,\s*['\"]klytron:deploy:failed_unlock_guard['\"]\s*\)/", $content),
            'Failure hook must route through the owned-lock-only guard'
        );
        $this->assertStringContainsString(
            "after('deploy:lock', 'klytron:deploy:mark_lock_acquired')",
            $content,
            'Lock ownership marker must be registered after deploy:lock'
        );
    }

    public function testStagingGuardExplainsTheConcurrentDeployCause(): void
    {
        $content = file_get_contents(self::PACKAGE_ROOT . 'klytron-tasks.php');

        $this->assertStringContainsString(
            'concurrent deploy',
            $content,
            'verify_staging abort message must name the concurrent-deploy cause'
        );
        $this->assertStringContainsString(
            'Nothing was switched for this run',
            $content,
            'verify_staging abort message must state that nothing was switched'
        );
    }
}
