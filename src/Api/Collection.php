<?php

namespace STS\HubSpot\Api;

use Illuminate\Support\Arr;
use STS\HubSpot\Api\Interfaces\ModelInterface;

class Collection extends \Illuminate\Support\Collection implements ModelInterface
{
    protected array $response = [];
    protected int $total = 0;
    protected $after;

    public static function hydrate(array $response, string $className): static
    {
        $instance = new static($response['results']);
        $instance = $instance->map(fn($payload) => $className::hydrate($payload));
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