<?php

namespace STS\HubSpot\Api;

class PropertyDefinition
{
    protected Collection $collection;

    public function __construct(protected Model $object, protected Builder $builder)
    {
    }

    public function get(): Collection
    {
        return $this->collection ?? $this->load();
    }

    public function load(): Collection
    {
        return $this->builder->properties()->keyBy('name');
    }
}