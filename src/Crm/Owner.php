<?php

namespace STS\HubSpot\Crm;

use Carbon\Carbon;
use STS\HubSpot\Api\Builder;
use STS\HubSpot\Api\Model;
use STS\HubSpot\Facades\HubSpot;

class Owner extends Model
{
    protected string $type = "owners";

    protected array $schema = [
        'email' => 'string',
        'firstName' => 'string',
        'lastName' => 'string',
        'userId' => 'integer',
        'groupName' => 'string',
        'options' => 'array',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime',
        'archived' => 'bool',
        'archivedAt' => 'datetime',
    ];

    protected array $endpoints = [
        "read" => "/v3/owners/{id}",
    ];

    protected function init(array $payload = []): static
    {
        $this->payload = $payload;
        $this->fill($payload);
        $this->exists = true;

        return $this;
    }
}