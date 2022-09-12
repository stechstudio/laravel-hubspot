<?php

namespace STS\HubSpot\Api;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\Conditionable;

class Builder
{
    use Conditionable;

    protected array $filters = [];
    protected string $query;
    protected array $sort;
    protected int $after;
    protected int $limit = 50;

    protected Model $object;
    protected string $objectClass;

    protected array $properties = [];
    protected array $with = [];

    public function __construct(protected Client $client)
    {
    }

    public function query(): static
    {
        return $this;
    }

    public function for(Model $object): static
    {
        $this->object = $object;
        $this->objectClass = get_class($object);

        return $this;
    }

    public function include($properties): static
    {
        $this->properties = is_array($properties)
            ? $properties
            : func_get_args();

        return $this;
    }

    public function with($associations): static
    {
        $this->with = is_array($associations)
            ? $associations
            : func_get_args();

        return $this;
    }

    public function create(array $properties): array
    {
        return $this->client()->post(
            $this->object->endpoint('create'),
            ['properties' => $properties]
        )->json();
    }

    public function find($id, $idProperty = null): Model
    {
        $response = $this->client()->get(
            $this->object->endpoint('read', ['id' => $id]),
            [
                'properties'   => implode(",", $this->includeProperties()),
                'associations' => implode(",", $this->includeAssociations()),
                'idProperty'   => $idProperty
            ]
        )->json();

        return ($this->hydrateObject($response))->has($this->includeAssociations());
    }

    public function findMany(array $ids, $idProperty = null): Collection
    {
        $ids = array_filter(array_unique($ids));

        if (count($ids) === 1) {
            return new Collection([$this->find($ids[0], $idProperty)]);
        }

        if (!count($ids)) {
            return new Collection();
        }

        $response = $this->client()->post(
            $this->object->endpoint('batchRead'),
            [
                'properties' => $this->includeProperties(),
                'idProperty' => $idProperty,
                'inputs'     => array_map(fn($id) => ['id' => $id], $ids)
            ]
        )->json();

        return Collection::hydrate($response, $this->objectClass);
    }

    public function update(array $properties): array
    {
        return $this->client()->patch(
            $this->object->endpoint('update', ['id' => $this->object->id]),
            ['properties' => $properties]
        )->json();
    }

    public function where($property, $condition, $value = null): static
    {
        $this->filters[] = new Filter($property, $condition, $value);

        return $this;
    }

    public function orderBy($property, $direction = 'ASC'): static
    {
        $this->sort = [
            'propertyName' => $property,
            'direction'    => strtoupper($direction) === 'DESC' ? 'DESCENDING' : 'ASCENDING'
        ];

        return $this;
    }

    public function take(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function skip(int $after): static
    {
        $this->after = $after;

        return $this;
    }

    public function search($input): static
    {
        $this->query = $input;

        return $this;
    }

    public function fetch($after = null, $limit = null): array
    {
        return $this->client()->post(
            $this->object->endpoint('search'),
            [
                'limit'        => $limit ?? $this->limit,
                'after'        => $after ?? $this->after ?? null,
                'query'        => $this->query ?? null,
                'properties'   => $this->includeProperties(),
                'sorts'        => isset($this->sort) ? [$this->sort] : null,
                'filterGroups' => [[
                    'filters' => array_map(fn($filter) => $filter->toArray(), $this->filters)
                ]]
            ]
        )->json();
    }

    public function get()
    {
        return Collection::hydrate(
            $this->fetch(),
            $this->objectClass
        );
    }

    public function cursor(): LazyCollection
    {
        return new LazyCollection(function () {
            $after = 0;

            do {
                $response = $this->fetch($after);
                $after = Arr::get($response, 'paging.next.after');

                foreach ($response['results'] as $payload) {
                    yield $this->hydrateObject($payload);
                }
            } while ($after !== null);
        });
    }

    public function paginate($perPage = 50, $pageName = 'page', $page = null): LengthAwarePaginator
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $results = Collection::hydrate(
            $this->fetch($perPage * $page, $perPage),
            $this->objectClass
        );

        return new LengthAwarePaginator(
            $results, $results->total(), $perPage, $page, [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

    public function count(): int
    {
        return Arr::get($this->get(1, 0, false), 'total', 0);
    }

    public function client(): Client
    {
        return $this->client;
    }

    public function associations($association): array
    {
        return $this->client()->get(
            $this->object->endpoint('associations', ['id' => $this->object->id, 'association' => $association])
        )->json()['results'];
    }

    protected function hydrateObject($payload): Model
    {
        $class = $this->objectClass;

        return $class::hydrate($payload);
    }

    protected function includeProperties(): array
    {
        return array_merge(
            config("hubspot.{$this->object->type()}.include_properties", []),
            $this->properties
        );
    }

    protected function includeAssociations(): array
    {
        return array_merge(
            config("hubspot.{$this->object->type()}.include_associations", []),
            $this->with
        );
    }

}