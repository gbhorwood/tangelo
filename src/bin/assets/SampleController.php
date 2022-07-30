<?php
namespace PROJECTNAMESPACE\Controllers;

use Swoole\Coroutine as co;
use Psr\Http\Message\ResponseInterface;
use \Ghorwood\Tangelo\TangeloController;
use \Ghorwood\Tangelo\Lookups\ConfigLookup as config;

class SampleController extends \Ghorwood\Tangelo\TangeloController
{

    /**
     * Sample controller method showing:
     *    - Getting config values from cache created from .env file
     *    - Getting one or all parameters from the endpoint path
     *    - Sending a json response
     */
    public function getThing():ResponseInterface
    {
        /**
         * Retrieve one value by it's key from the config cache.
         * The config cache is built from the .env file at server start
         */
        $userDefinedConfigValue = $this->getConfig('SAMPLE_CONFIG_VALUE');

        /**
         * Get the parameters passed in the path of the endpoint, either by
         * name or as complete associative array.
         */
        $id = $this->getPathParam('id');
        $allPathParams = $this->getPathParams();

        /**
         * Build and return an associative array as json
         */
        $thing = [
            'id' => $id,
            'name' => 'sample thing',
            'user_defined_config_value' => $userDefinedConfigValue,
            'all_path_parameters' => $allPathParams,
        ];

        return $this->jsonResponse($thing, 200);
    }


    /**
     * Sample controller method showing:
     *    - Getting params from the query string
     */
    public function getThingQueryString()
    {
        /**
         * Query string parameters can be retrieved by name or
         * a complete associative array. If time= is passed on the query
         * string here, it will be returned in the json body.
         */
        $queryParams = $this->getQueryParams(); // all query string
        $time = $this->getQueryParam('time'); // one value

        $thing = [
            'id' => $id,
            'name' => 'sample thing',
            'time' => $time ?? null,
        ];
        return $this->jsonResponse($thing, 200);
    }


    /**
     * Sample controller method showing:
     *    - Storing responses in the cache
     *    - Retrieving responses from the cache
     *    - Adding custom headers to the json responise
     */
    public function getThingCaching()
    {
        $id = $this->getPathParam('id');

        /**
         * Try to retrieve the response from the cache. If it is present, return
         * it. Cache values are keyed by a combination of the method name, __METHOD__ here,
         * and another value unique to this method, in this example the id.
         * Cache retrieve() returns either a string or, if the value is not found, false.
         */
        $cache = $this->cache->retrieve(__METHOD__, $id);
        if($cache !== false) {

            /**
             * An optional array of headers to add to the response.
             */
            $headers = [
                'X-Cached' => 'true'
            ];

            /**
             * only strings are cached, so json must be decoded for jsonResponse()
             */
            return $this->jsonResponse(json_decode($cache), 200, $headers);
        }

        $thing = [
            'id' => $id,
            'name' => 'sample thing',
            'timestamp' => time()
        ];

        /**
         * Store the response body in the cache as a string.
         * Keying is done by the method name using __METHOD__ and a value that
         * is unique to this method. The last parameter is the life of the
         * cache in seconds. If omitted, the default is 60.
         */
        $this->cache->store(__METHOD__, $id, json_encode($thing), 60);

        /**
         * An optional array of headers to add to the response.
         */
        $headers = [
            'X-Cached' => 'false'
        ];
        return $this->jsonResponse($thing, 200);
    }

    /**
     * Sample controller method showing:
     *    - How to query the database via PDO
     *    - How to run code in a coroutine
     *    - Basic logging to STDOUT
     */
    public function postThing()
    {
        /**
         * Code inside the go() call executes asynchronously(ish)
         * This is a 'fire and forget' controller method. Execution goes straight to the 
         * bottom of the method and jsonResponse() is called. The code inside the
         * go() call finishes after this method exits.
         *
         * @see https://openswoole.com/docs/modules/swoole-coroutine-create
         */
        go(function() {
            co::sleep(4); // sleep for four seconds to simulate a heavy db call

            /**
             * Sql is executed with PDO using the query() method (commented our here).
             * It accepts the sql statement and an array of arguments. It returns
             * an associative array of the results using PDO::FETCH_ASSOC
             */
            $sql =<<<SQL
            SELECT    *
            FROM      things
            WHERE     id = ?
            SQL;
            //$resultArray = $this->query($sql, [$id]);

            /**
             * Write a log to STDOUT. There is also a debug() method.
             */
            $this->log("Done sql statement");
        });

        return $this->jsonResponse(null, 200);
    }
}