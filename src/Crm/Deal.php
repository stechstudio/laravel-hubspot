<?php

namespace STS\HubSpot\Crm;

use STS\HubSpot\Api\Association;
use STS\HubSpot\Api\Collection;
use STS\HubSpot\Api\Model;

/**
 * @method Association contacts();
 * @method Association companies();
 * @method Association line_items();
 * @property-read LineItems|null $line_item;
 * @property-read Collection $line_items;
 * @property-read Contact|null $contact;
 * @property-read Collection $contacts;
 * @property-read Company|null $company;
 * @property-read Collection $companies;
 */
class Deal extends Model
{
    protected string $type = "deals";
}