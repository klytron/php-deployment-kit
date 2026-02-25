<?php

namespace Klytron\PhpDeploymentKit\Providers;

use Illuminate\Support\ServiceProvider;
use Klytron\PhpDeploymentKit\Commands\KlytronDbSearchReplaceCommand;
use Klytron\PhpDeploymentKit\Commands\KlytronFileDeCrypterCommand;
use Klytron\PhpDeploymentKit\Commands\KlytronFileEnCrypterCommand;
use Klytron\PhpDeploymentKit\Commands\KlytronStorageLinkCommand;
use Klytron\PhpDeploymentKit\Commands\KlytronSqliteSetterCommand;

class PhpDeploymentKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                KlytronDbSearchReplaceCommand::class,
                KlytronFileDeCrypterCommand::class,
                KlytronFileEnCrypterCommand::class,
                KlytronStorageLinkCommand::class,
                KlytronSqliteSetterCommand::class,
            ]);
        }
    }
}
