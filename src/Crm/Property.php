<?php

namespace STS\HubSpot\Crm;

use Carbon\Carbon;
use STS\HubSpot\Api\Builder;
use STS\HubSpot\Api\Collection;
use STS\HubSpot\Api\Model;
use STS\HubSpot\Facades\HubSpot;

class Property extends Model
{
    protected string $type = "properties";
    protected $targetType = "contacts";

    protected array $schema = [
        'name' => 'string',
        'label' => 'string',
        'type' => 'string',
        'fieldType' => 'string',
        'description' => 'string',
        'groupName' => 'string',
        'options' => 'array',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime',
        'archived' => 'bool',
        'archivedAt' => 'datetime',
    ];

    public function scopeLoad(Builder $builder, $targetType)
    {
        return HubSpot::factory($targetType)->properties();
    }

    protected function init(array $payload = []): static
    {
        $this->payload = $payload;
        $this->fill($payload);
        $this->exists = true;

        return $this;
    }

    public function unserialize($value): mixed
    {
        return match ($this->payload['type']) {
            'date'        => Carbon::parse($value),
            'datetime'    => Carbon::parse($value),
            'number'      => $value + 0,
            'enumeration' => is_null($value) ? [] : explode(";", $value),
            default       => $value,
        };
    }

    public function serialize($value): mixed
    {
        return match ($this->payload['type']) {
            'date'        => $value instanceof Carbon ? $value->toIso8601String() : $value,
            'dateTime'    => $value instanceof Carbon ? $value->format('Y-m-d') : $value,
            'number'      => $value + 0,
            'enumeration' => implode(';', $value),
            default       => $value,
        };
    }
}
