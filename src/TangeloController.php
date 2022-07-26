<?php
namespace Ghorwood\Tangelo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Bitty\Http\ResponseFactory;
use Bitty\Http\Response;

class TangeloController
{
    private Array $pathParams;
    private Array $queryParams;

    public function __construct(Array $pathParams, Array $queryParams) {
        $this->pathParams = $pathParams;
        $this->queryParams = $queryParams;
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

    protected function jsonResponse(Int $code, $content = null)
    {
        $response = new Response(
            json_encode($content),
            201,
            ['X-Foo' => 'bar']
        );
        return $response;
    }
}
