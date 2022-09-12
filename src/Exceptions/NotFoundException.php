<?php

namespace STS\HubSpot\Exceptions;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

class NotFoundException extends HttpClientException
{
    public $response;

    public function __construct(Response $response, RequestException $previous)
    {
        parent::__construct(
            'Resource not found: ' . $response->effectiveUri()->getPath(),
            $response->status(),
            $previous
        );

        $this->response = $response;
    }
}