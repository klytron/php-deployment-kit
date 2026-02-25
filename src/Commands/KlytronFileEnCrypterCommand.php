<?php

namespace Klytron\PhpDeploymentKit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;

class KlytronFileEnCrypterCommand extends Command
{
    protected $signature = 'klytron:file:encrypt {path} {--delete-original : Delete the original file after encryption}';
    protected $description = '[PhpDeploymentKit] Encrypt a file or all files in a directory using Laravel\'s Crypt facade.';

    protected $basePath;

    public function __construct()
    {
        parent::__construct();
        $this->basePath = base_path();
    }

    public function handle()
    {
        $path = $this->argument('path');
        $deleteOriginal = $this->option('delete-original');

        // Resolve the absolute path and validate it's within the project
        $absolutePath = $this->resolvePath($path);
        
        if (!$this->isPathWithinProject($absolutePath)) {
            $this->error("Error: Path must be within the project directory.");
            $this->error("Project path: {$this->basePath}");
            $this->error("Provided path: {$absolutePath}");
            return Command::FAILURE;
        }

        if (File::isDirectory($absolutePath)) {
            $files = File::allFiles($absolutePath);
            $files = array_filter($files, function ($file) {
                return !str_ends_with($file->getFilename(), '.encrypted');
            });

            if (empty($files)) {
                $this->info("No unencrypted files found in directory: {$path}");
                return Command::SUCCESS;
            }
            foreach ($files as $file) {
                $filePath = $file->getPathname();
                $result = $this->processFile($filePath, $deleteOriginal);
                if ($result !== Command::SUCCESS) {
                    $this->warn("Failed to process: {$filePath}");
                }
            }
            $this->info("Batch encryption completed for directory: {$path}");
            return Command::SUCCESS;
        } else {
            if (str_ends_with($absolutePath, '.encrypted')) {
                $this->info("File '{$path}' is already encrypted. Skipping.");
                return Command::SUCCESS;
            }
            return $this->processFile($absolutePath, $deleteOriginal);
        }
    }

    /**
     * Resolve relative path to absolute path
     */
    protected function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return realpath($path) ?: $path;
        }
        return realpath($this->basePath . '/' . $path) ?: ($this->basePath . '/' . $path);
    }

    /**
     * Check if path is within the project directory
     */
    protected function isPathWithinProject(string $path): bool
    {
        $realBasePath = realpath($this->basePath);
        $realPath = realpath($path);
        
        if ($realPath === false) {
            // Allow if parent directory is within project (for new files)
            $parentDir = dirname($realPath ?: $path);
            return str_starts_with($parentDir, $realBasePath ?: '');
        }
        
        return str_starts_with($realPath, $realBasePath ?: '');
    }

    protected function processFile($filePath, $deleteOriginal = false)
    {
        $encryptedFilePath = $filePath . '.encrypted';

        if (!File::exists($filePath)) {
            $this->error("Error: File not found at {$filePath} for encryption.");
            return Command::FAILURE;
        }

        try {
            $content = File::get($filePath);
            $encryptedContent = Crypt::encryptString($content);
            File::put($encryptedFilePath, $encryptedContent);
            $this->info("File '{$filePath}' encrypted successfully to '{$encryptedFilePath}'");
            if ($deleteOriginal) {
                File::delete($filePath);
                $this->info("Original file '{$filePath}' deleted.");
            }
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
