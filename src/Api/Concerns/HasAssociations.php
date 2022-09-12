<?php

namespace STS\HubSpot\Api\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use STS\HubSpot\Api\Association;

trait HasAssociations
{
    protected array $associations;
    protected array $preloaded = [];

    public function has(array $preloaded): static
    {
        $this->preloaded = $preloaded;

        return $this;
    }

    public function associations($type): Association
    {
        return new Association(
            self::factory($type),
            $this->getAssociationIDs($type)
        );
    }

    protected function getAssociationIDs($type): array
    {
        $results = array_key_exists($type, $this->preloaded)
            ? Arr::get($this->payload, "associations.$type.results", [])
            : $this->loadAssocationIDs($type);

        return Arr::pluck($results, 'id');
    }

    protected function loadAssocationIDs($type): array
    {
        return $this->builder()->associations($type);
    }

    public function getAssociations($type): Collection
    {
        if($this->associationsLoaded($type)) {
            return $this->associations[$type]->get();
        }

        return $this->loadAssociations($type);
    }

    public function associationsLoaded($type): bool
    {
        return isset($this->associations) && array_key_exists($type, $this->associations);
    }

    public function loadAssociations($type): Collection
    {
        $this->associations[$type] = $this->associations($type);

        return $this->associations[$type]->get();
    }
}