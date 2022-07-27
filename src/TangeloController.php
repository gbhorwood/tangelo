<?php
namespace Ghorwood\Tangelo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Bitty\Http\ResponseFactory;
use Bitty\Http\Response;
use Ghorwood\Tangelo\ConfigLookup as ConfigLookup;

class TangeloController
{
    private Array $pathParams;
    private Array $queryParams;
    private ConfigLookup $config;

    public function __construct(Array $pathParams, Array $queryParams, ConfigLookup $config) {
        $this->pathParams = $pathParams;
        $this->queryParams = $queryParams;
        $this->config = $config;
    }

    protected function getConfig(String $key, String $default = null):?String
    {
        return $this->config->get($key, $default);
    }

    protected function getPathParams()
    {
        return $this->pathParams;
    }

    protected function getPathParam(String $key):?String
    {
        return $this->pathParams[$key] ?? null;
    }

    protected function getQueryParams()
    {
        return $this->queryParams;
    }

    protected function getQueryParam(String $key):?String
    {
        return $this->queryParams[$key] ?? null;
    }

    protected function jsonResponse(Int $code, $content = null):Response
    {
        $response = new Response(
            json_encode($content),
            201,
            ['X-Foo' => 'bar']
        );
        return $response;
    }
}
