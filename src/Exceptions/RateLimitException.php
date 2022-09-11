<?php

namespace STS\HubSpot\Exceptions;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

class RateLimitException extends HttpClientException
{
    public $response;

    public function __construct(Response $response, RequestException $previous)
    {
        parent::__construct(
            $response->json('message', "HTTP request returned status code {$response->status()}"),
            $response->status(),
            $previous
        );

        $this->response = $response;
    }
}