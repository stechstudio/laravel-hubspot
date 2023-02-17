<?php

namespace STS\HubSpot\Api;

use BadMethodCallException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use STS\HubSpot\Crm\Property;
use STS\HubSpot\Exceptions\NotFoundException;

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

    protected array $defaultProperties = [];
    protected array $properties = [];

    protected array $defaultAssociations = [];
    protected array $associations = [];

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

        $this->defaultProperties = config("hubspot.{$this->object->type()}.include_properties", []);
        $this->defaultAssociations = config("hubspot.{$this->object->type()}.include_associations", []);

        return $this;
    }

    public function include($properties): static
    {
        $this->properties = is_array($properties)
            ? $properties
            : func_get_args();

        return $this;
    }

    public function includeOnly($properties): static
    {
        return $this->clearProperties()->include(...func_get_args());
    }

    /**
     * Alias of the above includeOnly() method
     */
    public function select($properties): static
    {
        return $this->includeOnly(...func_get_args());
    }

    public function clearProperties(): static
    {
        $this->defaultProperties = [];

        return $this;
    }

    public function full(): static
    {
        return $this->include(
            $this->definitions()->get()->keys()->all()
        );
    }

    public function with($associations): static
    {
        $this->associations = is_array($associations)
            ? $associations
            : func_get_args();

        return $this;
    }

    public function withOnly($associations): static
    {
        return $this->clearAssociations()->with(...func_get_args());
    }

    public function clearAssociations(): static
    {
        $this->defaultAssociations = [];

        return $this;
    }

    public function create(array $properties): array
    {
        return $this->client()->post(
            $this->object->endpoint('create'),
            ['properties' => $properties]
        )->json();
    }

    public function item($id, $idProperty = null): array
    {
        return $this->client()->get(
            $this->object->endpoint('read', ['id' => $id]),
            [
                'properties'   => implode(",", $this->includeProperties()),
                'associations' => implode(",", $this->includeAssociations()),
                'idProperty'   => $idProperty
            ]
        )->json();
    }

    public function find($id, $idProperty = null): Model|null
    {
        try {
           return $this->findOrFail($id, $idProperty);
        } catch (NotFoundException $e) {
            return null;
        }
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

    public function findOrFail($id, $idProperty = null): Model
    {
        return ($this->hydrateObject(
            $this->item($id, $idProperty)
        ))->has($this->includeAssociations());
    }

    public function update(array $properties): array
    {
        return $this->client()->patch(
            $this->object->endpoint('update'),
            ['properties' => $properties]
        )->json();
    }

    public function delete(): bool
    {
        $this->client()->delete(
            $this->object->endpoint('delete')
        );

        return true;
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

    public function items($after = null, $limit = null): array
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
            $this->items(),
            $this->objectClass
        );
    }

    public function cursor(): LazyCollection
    {
        return new LazyCollection(function () {
            $after = 0;

            do {
                $response = $this->items($after);
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
            $this->items($perPage * $page, $perPage),
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
        return $this->take(1)->get()->total();
    }

    public function first(): Model|null
    {
        return $this->take(1)->get()->first();
    }

    public function associate(Model $target, $targetId)
    {
        return $this->client()->put(
            $this->object->endpoint('associate', [
                'association' => $target->type(),
                'associationId' => $targetId,
                'associationType' => Str::singular($this->object->type()) . "_to_" . Str::singular($target->type())
            ])
        )->json();
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

    public function definitions()
    {
        return new PropertyDefinition($this->object, $this);
    }

    public function properties(): Collection
    {
        return Collection::hydrate(
            $this->client()->get(
                $this->object->endpoint('properties')
            )->json(),
            Property::class
        );
    }

    public function createDefinition(array $properties): Property
    {
        return Property::hydrate(
            $this->client()->post(
                $this->object->endpoint('properties'),
                $properties
            )->json()
        );
    }

    protected function hydrateObject($payload): Model
    {
        $class = $this->objectClass;

        return $class::hydrate($payload);
    }

    protected function includeProperties(): array
    {
        return array_merge($this->defaultProperties, $this->properties);
    }

    protected function includeAssociations(): array
    {
        return array_merge($this->defaultAssociations, $this->associations);
    }

    public function __call($method, $parameters)
    {
        if ($this->object->hasNamedScope($method)) {
            array_unshift($parameters, $this);

            $response = $this->object->callNamedScope($method, $parameters);

            return $response ?? $this;
        }

        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}