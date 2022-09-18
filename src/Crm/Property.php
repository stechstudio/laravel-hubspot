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
        return match($this->payload['type']) {
            'date' => Carbon::parse($value),
            'datetime' => Carbon::parse($value),
            'number' => $value + 0,
            'enumeration' => explode(";", $value),
            default => $value,
        };
    }

    public function serialize($value): mixed
    {
        return match($this->payload['type']) {
            'date' => $value instanceof Carbon ? $value->toIso8601String() : $value,
            'dateTime' => $value instanceof Carbon ? $value->format('Y-m-d') : $value,
            'number' => $value + 0,
            'enumeration' => implode(';', $value),
            default => $value,
        };
    }
}