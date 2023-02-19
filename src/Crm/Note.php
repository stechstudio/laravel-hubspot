<?php

namespace STS\HubSpot\Crm;

use STS\HubSpot\Api\Association;
use STS\HubSpot\Api\Collection;
use STS\HubSpot\Api\Model;

/**
 * @method Association contacts();
 * @method Association deals();
 * @property-read Contact|null $contact;
 * @property-read Collection $contacts;
 * @property-read Deal|null $deal;
 * @property-read Collection $deals;
 */
class Note extends Model
{
    protected string $type = "notes";
}