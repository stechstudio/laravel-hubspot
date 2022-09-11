<?php

namespace STS\HubSpot\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \STS\HubSpot\CRM
 */
class HubSpot extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \STS\HubSpot\CRM::class;
    }
}
