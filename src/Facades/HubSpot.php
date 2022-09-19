<?php

namespace STS\HubSpot\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \STS\HubSpot\Sdk
 */
class HubSpot extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \STS\HubSpot\Sdk::class;
    }
}
