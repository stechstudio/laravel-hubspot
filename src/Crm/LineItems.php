<?php

namespace STS\HubSpot\Crm;

use STS\HubSpot\Api\Association;
use STS\HubSpot\Api\Collection;
use STS\HubSpot\Api\Model;

/**
 * @method Association deals();
 * @method Association quotes();
 * @property-read Deal|null $deal;
 * @property-read Collection $deals;
 * @property-read Quote|null $quote;
 * @property-read Collection $quotes;
 */
class LineItems extends Model
{
    protected string $type = "line_items";
}