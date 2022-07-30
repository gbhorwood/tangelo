<?php
namespace Ghorwood\Tangelo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Swoole\Http\Table;
use Swoole\Coroutine as co;

use Bitty\Http\Response;
use Bitty\Http\ResponseFactory;

use Ghorwood\Tangelo\Mysql as Mysql;
use Ghorwood\Tangelo\Logger as Logger;
use Ghorwood\Tangelo\Lookups\CacheLookup as CacheLookup;
use Ghorwood\Tangelo\Lookups\ConfigLookup as ConfigLookup;

/**
 * Superclass for user-created controllers
 *
 */
class TangeloController
{

    /**
     * Associative array of parameters in the endpoint path
     */
    private array $pathParams;

    /**
     * Associative array of parameters in the query string
     */
    private array $queryParams;

    /**
     * Lookup for config values, wrapping a Swoole\Table
     */
    private ConfigLookup $config;

    /**
     * Mysql db access object
     */
    private Mysql $mysql;

    /**
     * The logger
     */
    private Logger $logger;

    /**
     * Lookup for Swoole\Table to hold user cached data
     */
    protected  CacheLookup $cache;

    /**
     * Super constructor
     *
     * @param  Array        $pathParams  Associative array of the parameters passed in the endpoint path
     * @param  Array        $queryParams Associative array of the parameters passed in the query string
     * @param  Mysql        $mysql       The Mysql object for querying
     * @param  ConfigLookup $config      The lookup wrapper to the Swoole\Table of configuration data
     * @param  CacheLookup  $cache       The lookup wrapper to the Swoole\Table that will hold user-cached data
     * @param  Logger       $logger      The log writer
     * @return void
     */
    public function __construct(array $pathParams, array $queryParams, Mysql $mysql, ConfigLookup $config, CacheLookup $cache, Logger $logger)
    {
        $this->pathParams = $pathParams;
        $this->queryParams = $queryParams;
        $this->config = $config;
        $this->cache = $cache;
        $this->mysql = $mysql;
        $this->logger = $logger;
    }


    /**
     * Do debug output
     *
     * @param  String $message
     * @return void
     */
    protected function debug(String $message):void
    {
        $this->logger->debug($message);
    }


    /**
     * Do log output
     *
     * @param  String $message
     * @return void
     */
    protected function log(String $message):void
    {
        $this->logger->log($message);
    }


    /**
     * Run a prepared PDO query and get the results
     *
     * @param  String $sql
     * @param  Array  $parameters
     * @return Array
     */
    protected function query(String $sql, array $parameters = []):array
    {
        return $this->mysql->query($sql, $parameters);
    }


    /**
     * Return one config value by its key, with optional default value.
     * Null returned if not found.
     *
     * @param  String $key      The key to search on
     * @param  String $default  The optional default value
     * @return ?String
     */
    protected function getConfig(String $key, String $default = null):?String
    {
        return $this->config->get($key, $default);
    }


    /**
     * Return all path parameters as an associative array.
     *
     * @return Array
     */
    protected function getPathParams():array
    {
        return $this->pathParams;
    }


    /**
     * Return one path parameter by its key.
     *
     * @param  String $key The key to search on
     * @return ?String
     */
    protected function getPathParam(String $key):?String
    {
        return $this->pathParams[$key] ?? null;
    }


    /**
     * Return all query string parameters as an associative array.
     *
     * @return Array
     */
    protected function getQueryParams():array
    {
        return $this->queryParams;
    }


    /**
     * Return one query string parameter by its key.
     *
     * @param  String $key The key to search on
     * @return ?String
     */
    protected function getQueryParam(String $key):?String
    {
        return $this->queryParams[$key] ?? null;
    }


    /**
     * Build a Psr7 Response object with a json body and optional headers.
     *
     * @param  Mixed $content The content to cast to json and set as return body
     * @param  Int   $code    The optional HTTP code. Default 200.
     * @param  Array $headers The optional array of headers
     * @return Response
     */
    protected function jsonResponse($content = null, Int $code = 200, array $headers = []):Response
    {
        $headers = array_merge($headers, ['Content-Type' => 'application/json']);
        $response = new Response(
            json_encode($content),
            $code,
            $headers
        );
        return $response;
    }
}
