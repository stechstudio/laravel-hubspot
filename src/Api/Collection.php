<?php

namespace STS\HubSpot\Api;

use Illuminate\Support\Arr;

class Collection extends \Illuminate\Support\Collection
{
    protected array $response = [];
    protected int $total = 0;
    protected $after;

    public static function hydrate(array $response, string $className): static
    {
        $instance = new static($response['results']);
        $instance = $instance->mapInto($className);
        $instance->response = $response;
        $instance->total = Arr::get($response, 'total', 0);
        $instance->after = Arr::get($response, 'paging.next.after');

        return $instance;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function response(): array
    {
        return $this->response;
    }
}