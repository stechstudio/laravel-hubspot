<?php

namespace STS\HubSpot\Api;

use Illuminate\Support\Traits\ForwardsCalls;

class Association
{
    use ForwardsCalls;

    protected Collection $collection;

    public function __construct(
        protected Model $source,
        protected Model $target,
        protected array $ids = []
    )
    {
    }

    public function get(): Collection
    {
        return $this->collection ?? $this->load();
    }

    public function load(): Collection
    {
        $this->collection = $this->builder()->findMany($this->ids);

        return $this->collection;
    }

    public function builder(): Builder
    {
        return app(Builder::class)->for($this->target);
    }

    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->builder(), $method, $parameters);
    }
}