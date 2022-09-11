<?php

namespace STS\HubSpot\Crm;

use STS\HubSpot\Api\Model;

class Company extends Model
{
    protected string $type = "companies";

    protected array $endpoints = [
        "read" => "/v3/objects/companies/{id}",
        "batchRead" => "/v3/objects/companies/batch/read",
        "search" => "/v3/objects/companies/search",
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