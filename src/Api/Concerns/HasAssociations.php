<?php

namespace STS\HubSpot\Api\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use STS\HubSpot\Api\Association;
use STS\HubSpot\Facades\HubSpot;

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
        if(!$this->associationsLoaded($type)) {
            $this->associations[$type] = new Association($this, HubSpot::factory($type), $this->getAssociationIDs($type));
        }

        return $this->associations[$type];
    }

    public function getAssociations($type): Collection
    {
        return $this->associations($type)->get();
    }

    public function associationsLoaded($type): bool
    {
        return isset($this->associations) && array_key_exists($type, $this->associations);
    }

    public function loadAssociations($type): Collection
    {
        return $this->associations($type)->load();
    }

    protected function getAssociationIDs($type): array
    {
        $results = in_array($type, $this->preloaded)
            ? Arr::get($this->payload, "associations.$type.results", [])
            : $this->loadAssocationIDs($type);

        return Arr::pluck($results, 'id');
    }

    protected function loadAssocationIDs($type): array
    {
        $this->preloaded[] = $type;
        $results = $this->builder()->associations($type);
        Arr::set($this->payload, "associations.$type.results", $results);

        return $results;
    }
}