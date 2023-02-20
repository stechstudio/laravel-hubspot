<?php

namespace STS\HubSpot\Crm;

use STS\HubSpot\Api\Association;
use STS\HubSpot\Api\Collection;
use STS\HubSpot\Api\Model;

/**
 * @method Association contacts()
 * @property-read Contact|null $contact
 * @property-read Collection $contacts
 */
class Meeting extends Model
{
    protected string $type = "meetings";
}