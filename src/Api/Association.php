<?php

namespace STS\HubSpot\Api;

use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;

class Association
{
    use ForwardsCalls;

    public function __construct(
        protected Model $object,
        protected array $ids
    )
    {}

    public function get(): Collection
    {
        return $this->builder()->findMany($this->ids);
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