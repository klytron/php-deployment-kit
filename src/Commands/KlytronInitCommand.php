<?php

namespace Klytron\PhpDeploymentKit\Commands;

use Illuminate\Console\Command;
use Klytron\PhpDeploymentKit\Generators\DeployConfigGenerator;

class KlytronInitCommand extends Command
{
    protected $signature = 'klytron:init
                            {app-name? : Application name (defaults to current folder name)}
                            {--template=laravel : Template to use (laravel, yii2, simple, generic)}
                            {--repo= : Git repository URL}
                            {--domain= : Production domain name}
                            {--host= : Production deploy host}
                            {--force : Overwrite existing deploy.php}';

    protected $description = '[PhpDeploymentKit] Initialize canonical deploy.php configuration with best practices pre-wired';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Initializing Klytron Deployer Configuration');
        $this->newLine();

        $appName = $this->argument('app-name');
        if (empty($appName)) {
            $appName = basename(getcwd());
        }

        $template = $this->option('template') ?: 'laravel';
        $force = (bool) $this->option('force');

        $options = array_filter([
            'repo'   => $this->option('repo'),
            'domain' => $this->option('domain'),
            'host'   => $this->option('host'),
        ]);

        $generator = new DeployConfigGenerator();

        try {
            $result = $generator->write(getcwd(), $appName, $template, $options, $force);

            $this->info("✅ Generated canonical deployment configuration for '{$appName}':");
            foreach ($result['files'] as $file) {
                $this->line("   - " . basename($file));
            }

            $this->newLine();
            $this->comment('Next steps:');
            $this->line('  1. Review and adjust <info>deploy.php</info>');
            $this->line('  2. Configure environment variables (e.g. <info>export DEPLOY_HOST=your-server.com</info>)');
            $this->line('  3. Validate deployment plan: <info>vendor/bin/dep klytron:plan</info>');
            $this->line('  4. Deploy: <info>vendor/bin/dep deploy</info>');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Failed to initialize configuration: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
