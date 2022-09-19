<?php

namespace STS\HubSpot;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use STS\HubSpot\Api\Client;

class HubSpotServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('hubspot')->hasConfigFile();

        $this->app->bind(Client::class, fn() => new Client(config('hubspot.access_token')));
    }
}
