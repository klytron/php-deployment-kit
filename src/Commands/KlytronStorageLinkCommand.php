<?php

namespace Klytron\PhpDeploymentKit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class KlytronStorageLinkCommand extends Command
{
    protected $signature = 'klytron:storage:link-clean';
    protected $description = '[PhpDeploymentKit] Remove existing storage links and create new ones based on config/filesystems.php links configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting storage link cleanup and creation...');

        // Get links configuration from filesystems config
        $links = Config::get('filesystems.links', []);

        if (empty($links)) {
            $this->warn('No links configured in config/filesystems.php');
            return 0;
        }

        $this->info('Found ' . count($links) . ' configured links to process.');

        // Process each link
        foreach ($links as $link => $target) {
            $this->processLink($link, $target);
        }

        $this->info('Storage link cleanup and creation completed successfully!');
        return 0;
    }

    /**
     * Process a single link: cleanup and create
     */
    private function processLink(string $linkPath, string $targetPath): void
    {
        $linkName = basename($linkPath);

        // Clean up existing link/directory
        if (is_link($linkPath)) {
            $this->info("Removing existing link: {$linkName}");
            unlink($linkPath);
            $this->info("✓ Existing link '{$linkName}' removed.");
        } elseif (File::exists($linkPath)) {
            $this->info("Removing existing directory/file: {$linkName}");
            if (is_dir($linkPath)) {
                File::deleteDirectory($linkPath);
            } else {
                File::delete($linkPath);
            }
            $this->info("✓ Existing directory/file '{$linkName}' removed.");
        } else {
            $this->info("No existing link/directory found for: {$linkName}");
        }

        // Ensure target directory exists
        if (!File::exists($targetPath)) {
            $this->info("Creating target directory: {$targetPath}");
            File::makeDirectory($targetPath, 0755, true);
        }

        // Create new symlink
        $this->info("Creating symlink: {$linkName} -> {$targetPath}");

        try {
            if (symlink($targetPath, $linkPath)) {
                $this->info("✓ Symlink '{$linkName}' created successfully.");
            } else {
                $this->error("Failed to create symlink '{$linkName}'");
            }
        } catch (\Exception $e) {
            $this->error("Error creating symlink '{$linkName}': " . $e->getMessage());
        }
    }
}
