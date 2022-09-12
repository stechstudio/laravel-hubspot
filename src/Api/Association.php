<?php

namespace STS\HubSpot\Api;

use Illuminate\Support\Traits\ForwardsCalls;

class Association
{
    use ForwardsCalls;

    protected Collection $collection;

    public function __construct(
        protected Model $object,
        protected array $ids = []
    )
    {
    }

    public function get(): Collection
    {
        if (!isset($this->collection)) {
            $this->collection = $this->builder()->findMany($this->ids);
        }

        return $this->collection;
    }

    public function builder(): Builder
    {
        return app(Builder::class)->for($this->object);
    }

    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->builder(), $method, $parameters);
    }
}