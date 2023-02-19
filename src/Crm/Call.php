<?php

namespace STS\HubSpot\Crm;

use STS\HubSpot\Api\Association;
use STS\HubSpot\Api\Collection;
use STS\HubSpot\Api\Model;

/**
 * @method Association contacts();
 * @method Association tickets();
 * @property-read Contact|null $contact
 * @property-read Collection $contacts
 * @property-read Ticket|null $ticket
 * @property-read Collection $tickets
 */
class Call extends Model
{
    protected string $type = "calls";
}