<?php

use Illuminate\Support\Facades\Http;
use STS\HubSpot\Api\Builder;
use STS\HubSpot\Api\Client;
use STS\HubSpot\Api\Model as AbstractApiModel;

beforeEach(function () {
    $this->client = new Client('test-token');
    $this->builder = new Builder($this->client);

    $this->model = new class extends AbstractApiModel {
        protected string $type = 'deals';
        protected array $endpoints = [
            'create' => '/v3/objects/{type}',
            'update' => '/v3/objects/{type}/{id}',
        ];
    };

    $this->builder->for($this->model);
});

test('create sends properties as a JSON object even when empty', function () {
    Http::fake([
        '*' => Http::response(['id' => '1', 'properties' => []], 201),
    ]);

    $this->builder->create([]);

    Http::assertSent(function ($request) {
        return str_contains($request->body(), '"properties":{}');
    });
});

test('create sends properties as a JSON object when populated', function () {
    Http::fake([
        '*' => Http::response(['id' => '1', 'properties' => ['dealname' => 'Test']], 201),
    ]);

    $this->builder->create(['dealname' => 'Test']);

    Http::assertSent(function ($request) {
        return str_contains($request->body(), '"properties":{"dealname":"Test"}');
    });
});

test('update sends properties as a JSON object even when empty', function () {
    Http::fake([
        '*' => Http::response(['id' => '1', 'properties' => []], 200),
    ]);

    (new ReflectionProperty($this->model, 'payload'))->setValue($this->model, ['id' => '1']);

    $this->builder->update([]);

    Http::assertSent(function ($request) {
        return str_contains($request->body(), '"properties":{}');
    });
});

test('update sends properties as a JSON object when populated', function () {
    Http::fake([
        '*' => Http::response(['id' => '1', 'properties' => ['dealname' => 'Updated']], 200),
    ]);

    (new ReflectionProperty($this->model, 'payload'))->setValue($this->model, ['id' => '1']);

    $this->builder->update(['dealname' => 'Updated']);

    Http::assertSent(function ($request) {
        return str_contains($request->body(), '"properties":{"dealname":"Updated"}');
    });
});
