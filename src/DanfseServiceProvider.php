<?php

namespace Danfse\Danfse;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Danfse\Danfse\Commands\DanfseCommand;

class DanfseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('danfse-php')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_danfse_php_table')
            ->hasCommand(DanfseCommand::class);
    }
}
