<?php

namespace Klytron\PhpDeploymentKit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KlytronSqliteSetterCommand extends Command
{
    protected $signature = 'klytron:sqlite:setup
                            {--database= : Custom database filename (without .sqlite extension)}
                            {--location=database : Directory location for the SQLite file}
                            {--force : Force recreate the database file if it exists}';

    protected $description = '[PhpDeploymentKit] Set up SQLite database file with configurable name and location';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🗄️  Setting up SQLite Database');
        $this->newLine();

        // Get configuration values
        $sqliteDbLocation = $this->option('location') ?: 'database';
        $databaseName = $this->getDatabaseName();
        $dbFile = $sqliteDbLocation . '/' . $databaseName . '.sqlite';
        $forceRecreate = $this->option('force');

        $this->info("📍 Database location: {$sqliteDbLocation}");
        $this->info("📄 Database file: {$databaseName}.sqlite");
        $this->newLine();

        if (Storage::disk('local')->exists($dbFile)) {
            $dbFileFullFilePath = Storage::disk('local')->path($dbFile);

            if ($forceRecreate) {
                $this->warn("🔄 Force recreating existing database file");
                Storage::disk('local')->delete($dbFile);
                $this->createDatabaseFile($sqliteDbLocation, $dbFile);
            } else {
                $this->info("✅ SQLite database file already exists: {$dbFileFullFilePath}");
            }
        } else {
            $this->warn("⚠️  SQLite database file does not exist");
            $this->createDatabaseFile($sqliteDbLocation, $dbFile);
        }

        return Command::SUCCESS;
    }

    /**
     * Get the database name from various sources
     */
    private function getDatabaseName(): string
    {
        // 1. Check if custom database name is provided via option
        if ($customName = $this->option('database')) {
            return Str::slug($customName, '_');
        }

        // 2. Check if database name is configured in config/database.php
        $sqliteConfig = config('database.connections.sqlite');
        if (isset($sqliteConfig['database']) && $sqliteConfig['database'] !== ':memory:') {
            $configDbPath = $sqliteConfig['database'];
            $filename = pathinfo($configDbPath, PATHINFO_FILENAME);
            if ($filename && $filename !== 'database') {
                return $filename;
            }
        }

        // 3. Derive from app name
        $appName = config('app.name', 'Laravel');
        $cleanAppName = Str::slug($appName, '_');

        // 4. Fallback to a generic name based on environment
        $environment = config('app.env', 'local');

        return $cleanAppName . '_' . $environment;
    }

    /**
     * Create the database file and directory
     */
    private function createDatabaseFile(string $sqliteDbLocation, string $dbFile): void
    {
        // Create directory if it doesn't exist
        if (!Storage::disk('local')->directoryExists($sqliteDbLocation)) {
            Storage::disk('local')->makeDirectory($sqliteDbLocation);
            $this->info("📁 Created database directory: {$sqliteDbLocation}");
        }

        // Create the SQLite file
        Storage::disk('local')->put($dbFile, '');

        $dbFileFullFilePath = Storage::disk('local')->path($dbFile);
        $this->info("✅ Successfully created SQLite database file: {$dbFileFullFilePath}");

        // Set appropriate permissions
        if (file_exists($dbFileFullFilePath)) {
            chmod($dbFileFullFilePath, 0664);
            $this->info("🔐 Set file permissions to 664");
        }
    }
}
