<?php

namespace STS\HubSpot\Api;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;
use STS\HubSpot\Api\Concerns\HasAssociations;
use STS\HubSpot\Api\Concerns\HasPropertyDefinitions;
use STS\HubSpot\Api\Interfaces\ModelInterface;
use STS\HubSpot\Crm\Owner;
use STS\HubSpot\Crm\Property;
use STS\HubSpot\Facades\HubSpot;

/**
 * @property-read Collection $definitions
 * @property-read int $id
 * @method array properties()
 */
abstract class Model implements ModelInterface
{
    use ForwardsCalls, Macroable, HasAssociations, HasPropertyDefinitions;

    protected string $type;

    protected array $payload = [];
    protected array $properties = [];
    public bool $exists = false;

    protected array $schema = [
        'id'                    => 'int',
        'properties'            => 'array',
        'propertiesWithHistory' => 'array',
        'associations'          => 'array',
        'createdAt'             => 'datetime',
        'updatedAt'             => 'datetime',
        'archived'              => 'bool',
        'archivedAt'            => 'datetime',
    ];

    protected array $endpoints = [
        "create" => "/v3/objects/{type}",
        "read" => "/v3/objects/{type}/{id}",
        "batchRead" => "/v3/objects/{type}/batch/read",
        "update" => "/v3/objects/{type}/{id}",
        "delete" => "/v3/objects/{type}/{id}",
        "search" => "/v3/objects/{type}/search",
        "associate" => "/v3/objects/{type}/{id}/associations/{association}/{associationId}/{associationType}",
        "associations" => "/v3/objects/{type}/{id}/associations/{association}",
        "properties" => "/v3/properties/{type}",
    ];

    protected array $allowedproperties = [
        'email',
        'company'
    ];

    public function __construct(array $properties = [])
    {
        if (empty($properties)) {
            return;
        }
        $this->fill($properties);
    }

    public static function hydrate(array $payload = []): self
    {
        $instance = new static;
        $instance->init($payload);

        return $instance;
    }

    protected function init(array $payload = []): static
    {
        $this->payload = $payload;
        $this->fill($payload['properties']);
        $this->exists = true;

        return $this;
    }

    public function fill(array $properties): static
    {
        if (empty($properties)) {
            return $this;
        }

        $properties = array_filter(
            $properties,
            fn (string $key): bool => $this->isAllowedProperty($key),
            ARRAY_FILTER_USE_KEY
        );

        $this->properties = array_merge($this->properties, $properties);

        return $this;
    }

    private function isAllowedProperty(string $key): bool
    {
        return in_array($key, $this->allowedproperties) ||
               !(HubSpot::isType($key) || HubSpot::isType(Str::plural($key)));
    }

    public function type(): string
    {
        return $this->type;
    }

    public function endpoint($key, $fill = []): string
    {
        $fill['type'] = $this->type;

        if (Arr::has($this->payload, 'id')) {
            $fill['id'] = $this->getFromPayload('id');
        }

        return str_replace(
            array_map(fn($key) => "{" . $key . "}", array_keys($fill)),
            array_values($fill),
            $this->endpoints[$key]
        );
    }

    public function expand(): static
    {
        return $this->init(
            $this->builder()->full()->item($this->id)
        );
    }

    public static function create(array $properties = []): static
    {
        return static::hydrate(
            static::query()->create($properties)
        );
    }

    public function update(array $properties = []): static
    {
        return $this->fill($properties)->save();
    }

    public function delete(): bool
    {
        $this->builder()->delete();
        $this->exists = false;

        return true;
    }

    public function save(): static
    {
        return $this->exists
            ? $this->fill($this->builder()->update($this->getDirty())['properties'])
            : $this->init($this->builder()->create($this->properties));
    }

    public function builder(): Builder
    {
        return app(Builder::class)->for($this);
    }

    public static function query(): Builder
    {
        return (new static())->builder();
    }

    public function getFromPayload($key, $default = null): mixed
    {
        return $this->cast(
            Arr::get($this->payload, $key, $default),
            Arr::get($this->schema, $key)
        );
    }

    public function getFromProperties($key): mixed
    {
        $value = Arr::get($this->properties, $key);

        return !is_a($this, Owner::class) && !is_a($this, Property::class) && $this->definitions->has($key)
            ? $this->definitions->get($key)->unserialize($value)
            : $value;
    }

    public function getDirty(): array
    {
        $dirty = [];
        $original = Arr::get($this->payload, 'properties', []);

        foreach ($this->properties as $key => $value) {
            if (! array_key_exists($key, $original) || $original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    protected function cast($value, $type = null): mixed
    {
        return match ($type) {
            'int'      => (int)$value,
            'datetime' => Carbon::parse($value),
            'array'    => (array)$value,
            'string'   => (string)$value,
            default    => $value
        };
    }

    public function hasNamedScope($scope): bool
    {
        return method_exists($this, 'scope'.ucfirst($scope));
    }

    public function callNamedScope($scope, array $parameters = [])
    {
        return $this->{'scope'.ucfirst($scope)}(...$parameters);
    }

    public function only($attributes): array
    {
        $results = [];

        foreach (is_array($attributes) ? $attributes : func_get_args() as $attribute) {
            $results[$attribute] = $this->getFromProperties($attribute);
        }

        return $results;
    }

    public function toArray(): array
    {
        return $this->payload;
    }

    public function __get($key)
    {
        if ($key === "definitions") {
            return $this->builder()->definitions()->get();
        }

        if (array_key_exists($key, $this->properties)) {
            return $this->getFromProperties($key);
        }

        if (array_key_exists($key, $this->payload)) {
            return $this->getFromPayload($key);
        }

        if (HubSpot::isType($key)) {
            return $this->getAssociations($key);
        }

        if (HubSpot::isType(Str::plural($key))) {
            return $this->getAssociations(Str::plural($key))->first();
        }

        return null;
    }

    public function __set($key, $value)
    {
        if ($this->isAllowedProperty($key)) {
            $this->properties[$key] = $value;
        }
    }

    public function __isset($key)
    {
        return isset($this->properties[$key]);
    }

    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    public function __call($method, $parameters)
    {
        if (HubSpot::isType($method)) {
            return $this->associations($method);
        }

        return $this->forwardCallTo($this->builder(), $method, $parameters);
    }
}
