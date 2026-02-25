<?php

namespace Klytron\PhpDeploymentKit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;

class KlytronFileDeCrypterCommand extends Command
{
    protected $signature = 'klytron:file:decrypt {path} {--delete-original : Delete the .encrypted file after decryption}';
    protected $description = '[PhpDeploymentKit] Decrypt a file or all files in a directory using Laravel\'s Crypt facade.';

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
                return str_ends_with($file->getFilename(), '.encrypted');
            });

            if (empty($files)) {
                $this->info("No encrypted files found in directory: {$path}");
                return Command::SUCCESS;
            }
            foreach ($files as $file) {
                $filePath = preg_replace('/\.encrypted$/', '', $file->getPathname());
                $result = $this->processFile($filePath, $deleteOriginal);
                if ($result !== Command::SUCCESS) {
                    $this->warn("Failed to process: {$filePath}");
                }
            }
            $this->info("Batch decryption completed for directory: {$path}");
            return Command::SUCCESS;
        } else {
            if (!str_ends_with($absolutePath, '.encrypted')) {
                $this->info("File '{$path}' is not an encrypted file. Skipping.");
                return Command::SUCCESS;
            }
            $filePath = preg_replace('/\.encrypted$/', '', $absolutePath);
            return $this->processFile($filePath, $deleteOriginal);
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
            $parentDir = dirname($realPath ?: $path);
            return str_starts_with($parentDir, $realBasePath ?: '');
        }
        
        return str_starts_with($realPath, $realBasePath ?: '');
    }

    protected function processFile($filePath, $deleteOriginal = false)
    {
        $encryptedFilePath = $filePath . '.encrypted';

        if (!File::exists($encryptedFilePath)) {
            $this->error("Error: Encrypted file not found at {$encryptedFilePath} for decryption.");
            return Command::FAILURE;
        }

        try {
            $encryptedContent = File::get($encryptedFilePath);
            $decryptedContent = Crypt::decryptString($encryptedContent);
            File::put($filePath, $decryptedContent);
            $this->info("File '{$encryptedFilePath}' decrypted successfully to '{$filePath}'");
            if ($deleteOriginal) {
                File::delete($encryptedFilePath);
                $this->info("Encrypted file '{$encryptedFilePath}' deleted.");
            }
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            $this->error("Decryption failed: " . $e->getMessage() . " (Possible incorrect APP_KEY or corrupted data)");
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
