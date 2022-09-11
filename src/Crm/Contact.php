<?php

namespace STS\HubSpot\Crm;

use STS\HubSpot\Api\Model;

class Contact extends Model
{
    protected string $type = "contacts";

    protected array $endpoints = [
        "read" => "/v3/objects/contacts/{id}",
        "batchRead" => "/v3/objects/contacts/batch/read",
        "search" => "/v3/objects/contacts/search",
    ];

    protected array $schema = [
        'id' => 'int',
        'properties' => 'array',
        'propertiesWithHistory' => 'array',
        'associations' => 'array',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime',
        'archived' => 'bool',
        'archivedAt' => 'datetime',
    ];
}