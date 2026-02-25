<?php

namespace Klytron\PhpDeploymentKit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KlytronDbSearchReplaceCommand extends Command
{
    protected $signature = 'klytron:db:search-replace {devurl} {produrl} {--dry-run} {--force}';
    protected $description = '[PhpDeploymentKit] Search and replace all occurrences of devurl with produrl in the database.';

    public function handle()
    {
        $devurl = $this->argument('devurl');
        $produrl = $this->argument('produrl');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Validate URLs
        if (!$this->validateUrls($devurl, $produrl)) {
            return self::FAILURE;
        }

        $this->info("Starting search and replace: '$devurl' → '$produrl'");
        if ($dryRun) {
            $this->comment('Dry run mode: No changes will be made.');
        } elseif (!$force) {
            if (!$this->confirm('Are you sure you want to perform this operation? This will modify your database.', false)) {
                $this->warn('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        $tables = $connection->getDoctrineSchemaManager()->listTableNames();

        // Tables to skip (system tables)
        $skipTables = ['migrations', 'password_resets', 'failed_jobs', 'personal_access_tokens'];
        $totalReplacements = 0;
        $summary = [];

        foreach ($tables as $table) {
            if (in_array($table, $skipTables)) {
                continue;
            }
            $columns = Schema::getColumnListing($table);
            $stringColumns = [];
            foreach ($columns as $column) {
                $type = Schema::getColumnType($table, $column);
                if (in_array($type, ['string', 'text', 'mediumText', 'longText', 'json'])) {
                    $stringColumns[] = $column;
                }
            }

            if (empty($stringColumns)) {
                continue;
            }

            $tableReplacements = 0;
            foreach ($stringColumns as $column) {
                // Use parameterized query to prevent SQL injection
                $query = DB::table($table)->where($column, 'LIKE', "%{$devurl}%");
                $count = $query->count();

                if ($count > 0) {
                    $summary[] = "Table: $table, Column: $column, Rows: $count";
                    $tableReplacements += $count;

                    if (!$dryRun) {
                        // Use parameter binding to prevent SQL injection
                        DB::table($table)
                            ->where($column, 'LIKE', "%{$devurl}%")
                            ->update([
                                $column => DB::raw("REPLACE(`$column`, ?, ?)"),
                            ], [$devurl, $produrl]);
                    }
                }
            }

            if ($tableReplacements > 0) {
                $summary[] = "Table '$table': $tableReplacements replacements";
                $totalReplacements += $tableReplacements;
            }
        }

        $this->newLine();
        $this->info('=== SEARCH AND REPLACE SUMMARY ===');
        if (empty($summary)) {
            $this->comment('No occurrences found.');
        } else {
            foreach ($summary as $line) {
                $this->line($line);
            }
            $this->newLine();
            $this->info("Total replacements: $totalReplacements");

            if ($dryRun) {
                $this->comment('This was a dry run. No changes were made.');
            } else {
                $this->info('All replacements completed successfully!');
            }
        }

        return self::SUCCESS;
    }

    /**
     * Validate the input URLs
     */
    protected function validateUrls(string $devurl, string $produrl): bool
    {
        // Validate that URLs are not empty
        if (empty(trim($devurl))) {
            $this->error('Dev URL cannot be empty.');
            return false;
        }

        if (empty(trim($produrl))) {
            $this->error('Production URL cannot be empty.');
            return false;
        }

        // Validate URL format (basic check for http/https)
        if (!filter_var($devurl, FILTER_VALIDATE_URL) && !str_starts_with($devurl, 'http')) {
            $this->warn('Warning: Dev URL does not appear to be a valid URL.');
        }

        if (!filter_var($produrl, FILTER_VALIDATE_URL) && !str_starts_with($produrl, 'http')) {
            $this->warn('Warning: Production URL does not appear to be a valid URL.');
        }

        return true;
    }
}
