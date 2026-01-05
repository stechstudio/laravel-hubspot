<?php

use Illuminate\Support\Facades\Http;
use STS\HubSpot\Api\Client;
use STS\HubSpot\Exceptions\HubSpotApiException;
use STS\HubSpot\Exceptions\InvalidRequestException;
use STS\HubSpot\Exceptions\NotFoundException;
use STS\HubSpot\Exceptions\RateLimitException;

beforeEach(function () {
    config()->set('hubspot.http.timeout', 10);
    config()->set('hubspot.http.connect_timeout', 10);
});

test('throws InvalidRequestException for 400 status', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Bad request'], 400),
    ]);

    $client = new Client('test-token');

    expect(fn() => $client->get('/v3/objects/contacts'))
        ->toThrow(InvalidRequestException::class);
});

test('throws NotFoundException for 404 status', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Not found'], 404),
    ]);

    $client = new Client('test-token');

    expect(fn() => $client->get('/v3/objects/contacts/123'))
        ->toThrow(NotFoundException::class);
});

test('throws InvalidRequestException for 409 status', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Conflict'], 409),
    ]);

    $client = new Client('test-token');

    expect(fn() => $client->post('/v3/objects/contacts'))
        ->toThrow(InvalidRequestException::class);
});

test('throws RateLimitException for rate limit response', function () {
    Http::fake([
        '*' => Http::response(['category' => 'RATE_LIMITS', 'message' => 'Rate limit exceeded'], 429),
    ]);

    $client = new Client('test-token');

    expect(fn() => $client->get('/v3/objects/contacts'))
        ->toThrow(RateLimitException::class);
});

test('throws HubSpotApiException for 401 unauthorized status', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Unauthorized'], 401),
    ]);

    $client = new Client('test-token');

    expect(fn() => $client->get('/v3/objects/contacts'))
        ->toThrow(HubSpotApiException::class);
});

test('throws HubSpotApiException for 403 forbidden status', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Forbidden'], 403),
    ]);

    $client = new Client('test-token');

    expect(fn() => $client->get('/v3/objects/contacts'))
        ->toThrow(HubSpotApiException::class);
});

test('throws HubSpotApiException for 422 unprocessable entity status', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Unprocessable Entity'], 422),
    ]);

    $client = new Client('test-token');

    expect(fn() => $client->post('/v3/objects/contacts'))
        ->toThrow(HubSpotApiException::class);
});

test('throws HubSpotApiException for 500 server error status', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Internal Server Error'], 500),
    ]);

    $client = new Client('test-token');

    expect(fn() => $client->get('/v3/objects/contacts'))
        ->toThrow(HubSpotApiException::class);
});

test('HubSpotApiException contains response object', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Unauthorized', 'correlationId' => '123'], 401),
    ]);

    $client = new Client('test-token');

    try {
        $client->get('/v3/objects/contacts');
        $this->fail('Expected HubSpotApiException to be thrown');
    } catch (HubSpotApiException $e) {
        expect($e->response)->not->toBeNull();
        expect($e->response->status())->toBe(401);
        expect($e->response->json('correlationId'))->toBe('123');
    }
});

test('HubSpotApiException message includes status code when no message in response', function () {
    Http::fake([
        '*' => Http::response([], 401),
    ]);

    $client = new Client('test-token');

    try {
        $client->get('/v3/objects/contacts');
        $this->fail('Expected HubSpotApiException to be thrown');
    } catch (HubSpotApiException $e) {
        expect($e->getMessage())->toContain('401');
    }
});

test('HubSpotApiException uses response message when available', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Missing required scopes'], 403),
    ]);

    $client = new Client('test-token');

    try {
        $client->get('/v3/objects/contacts');
        $this->fail('Expected HubSpotApiException to be thrown');
    } catch (HubSpotApiException $e) {
        expect($e->getMessage())->toBe('Missing required scopes');
    }
});

test('successful request does not throw exception', function () {
    Http::fake([
        '*' => Http::response(['results' => []], 200),
    ]);

    $client = new Client('test-token');
    $response = $client->get('/v3/objects/contacts');

    expect($response->successful())->toBeTrue();
    expect($response->json('results'))->toBe([]);
});
