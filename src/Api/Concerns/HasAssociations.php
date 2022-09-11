<?php

namespace STS\HubSpot\Api\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use STS\HubSpot\Api\Association;

trait HasAssociations
{
    protected array $associations;

    public function associations($type): Association
    {
        if(!array_key_exists('associations', $this->payload)) {
            $this->payload['associations'] = $this->builder()->find($this->id)->get('associations');
        }

        return new Association(
            self::factory($type),
            $this->getAssociationIDs($type)
        );
    }

    protected function getAssociationIDs($type): array
    {
        return Arr::pluck(
            Arr::get($this->payload, "associations.$type.results", [])
        , 'id');
    }

    public function getAssociations($type): Collection
    {
        if($this->associationsLoaded($type)) {
            return $this->associations[$type];
        }

        return $this->loadAssociations($type);
    }

    public function associationsLoaded($type): bool
    {
        return isset($this->associations) && array_key_exists($type, $this->associations);
    }

    public function loadAssociations($type): Collection
    {
        $this->associations[$type] = $this->associations($type)->get();

        return $this->associations[$type];
    }
}