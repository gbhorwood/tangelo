<?php
namespace Ghorwood\Tangelo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Swoole\Coroutine as co;
use Swoole\Http\Table;

use Bitty\Http\Response;
use Bitty\Http\ResponseFactory;

use Ghorwood\Tangelo\Mysql as Mysql;
use Ghorwood\Tangelo\Logger as Logger;
use Ghorwood\Tangelo\Lookups\ConfigLookup as ConfigLookup;

class TangeloController
{
    private Array $pathParams;
    private Array $queryParams;
    private ConfigLookup $config;
    private Mysql $mysql;
    private Logger $logger;

    public function __construct(Array $pathParams, Array $queryParams, Mysql $mysql, ConfigLookup $config, Logger $logger) {
        $this->pathParams = $pathParams;
        $this->queryParams = $queryParams;
        $this->config = $config;
        $this->mysql = $mysql;
        $this->logger = $logger;
    }


    /**
     * Run a prepared pdo query and get the results
     *
     * @param  String $sql
     * @param  Array  $parameters
     * @return Array
     */
    protected function query(String $sql, array $parameters = []):Array
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
    protected function getPathParams():Array
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
    protected function getQueryParams():Array
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

    protected function cache(String $method, String $identifier, String $data):bool
    {
        $expirySeconds = 60;
        $expiryTs = time() + $expirySeconds;

        if(!strlen(trim($method)) || !strlen(trim(strval($identifier)))) {
            $this->logger->error("Could not cache on a null key");
            return false;
        }
        $key = md5($method)."::".$identifier;
        echo strlen($key);

        ###############################################
        #
        # NOTE
        # create the cache table in httpServer and inject
        #
        #

        /**
         * Create Swoole Table if not exists
        if(!$this->cache) {
            $this->logger->debug("creating cache table");
            $this->cache = new \Swoole\Table(1024000);
            $this->cache->column('data', \Swoole\Table::TYPE_STRING, 512);
            $this->cache->column('expiry_ts', \Swoole\Table::TYPE_INT, 4);
            $this->cache->create();
        }
        $this->cache->set($key, ['data' => $data, 'expiry_ts' => $expiryTs]);
         */

        return true;
    }

    /**
     * Build a Psr7 Response object with a json body and optional headers.
     *
     * @param  Mixed $content The content to cast to json and set as return body
     * @param  Int   $code    The optional HTTP code. Default 200.
     * @param  Array $headers The optional array of headers
     * @return Response
     */
    protected function jsonResponse($content = null, Int $code = 200, Array $headers = []):Response
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
