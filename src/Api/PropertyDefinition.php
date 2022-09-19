<?php

namespace STS\HubSpot\Api;

use Illuminate\Cache\TaggedCache;
use Illuminate\Support\Facades\Cache;
use STS\HubSpot\Facades\HubSpot;

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
        return $this->cache()->has($this->cacheKey())
            ? $this->cache()->get($this->cacheKey())
            : $this->refresh();
    }

    public function refresh(): Collection
    {
        $definitions = $this->builder->properties()->keyBy('name');

        if(HubSpot::shouldCacheDefinitions()) {
            $this->cache()->put($this->cacheKey(), $definitions, HubSpot::definitionCacheTtl());
        }

        return $definitions;
    }

    protected function cache(): TaggedCache
    {
        return Cache::tags(['hubspot','hubspot-definitions']);
    }

    protected function cacheKey(): string
    {
        return "hubspot.{$this->object->type()}.definitions";
    }
}