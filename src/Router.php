<?php
namespace Ghorwood\Tangelo;

use Ghorwood\Tangelo\ConfigLookup as ConfigLookup;
use Ghorwood\Tangelo\RoutesLookup as RoutesLookup;
use Ghorwood\Tangelo\Logger as Logger;
use Ghorwood\Tangelo\Exceptions\RouterException as RouterException;

class Router
{
    /**
     */
    private ConfigLookup $configLookup;
    private RoutesLookup $routesLookup;

    private Logger $logger;

    /**
     * Constructor
     */
    public function __construct(RoutesLookup $routesLookup, ConfigLookup $configLookup, Logger $logger)
    {
        $this->routesLookup = $routesLookup;
        $this->configLookup = $configLookup;
        $this->logger = $logger;
    }


    /**
     * Compares a requests method and endpoint against all of the routes in the route table
     * and returns the first match with path arguments separated.
     *
     * eg.
     * request GET /things/12/stuff/23
     * route   GET /things/{thingid}/stuff/{stuffid} FancyController.getThingsStuff
     * returns [
     *            'function' => 'FancyController.getThingsStuff',
     *            'path_args' => [
     *                              'thingid' => 12,
     *                              'stuffid' => 23
     *                           ]
     *         ]
     *
     * @param  String $method   The HTTP method of the request, ie 'GET', 'POST' &c.
     * @param  String $endpoint The enpoint of the request, ie /things/12
     * @return Array
     * @throws RouterException
     */
    public function getRoute(String $method, String $endpoint):array
    {
        /**
         * Load the route table from the RoutesLookup wrapper for the Swoole\Table
         * containing all the routes.
         */
        $routeTable = $this->getRouteTable();

        /**
         * Test every route in the route table, handle first one that matches the request
         */
        foreach ($routeTable as $routeEndpoint => $routeMethodsArray) {

            /**
             * Build a list of keys for path arguments. so, ie. the route endpoint
             * /things/{thingid}/stuff/{stuffid}
             * becomes an array of keys for path arguments ['thingid', 'stuffid']
             */
            $pathArgTokens = [];
            preg_match_all('!({[a-zA-Z0-9_-]+})!', $routeEndpoint, $pathArgTokens);
            $pathArgKeys = array_map(fn ($p) => trim($p, "{} "), $pathArgTokens[0]);

            /**
             * Replace, in the route endpoint, the path argument identifiers with regexes
             * that will be used to test against the request.
             */
            $routeEndpointRegexEx = '!^'.preg_replace('!({[a-zA-Z0-9_-]+})!', '([a-zA-Z0-9_]+)', $routeEndpoint).'$!';

            /**
             * If this route matches the endpoint from teh request, it is a match
             * Extract the values for the path arguments into the array $pathArgVals
             */
            if (preg_match($routeEndpointRegexEx, $endpoint, $pathArgVals)) {

                // remove first element of pathArgVals as it is the full string and of no use
                array_shift($pathArgVals);

                /**
                 * Test for HTTP 405
                 * If this route endpoint does not have an entry for the HTTP method of the request
                 * that is an error of type 405.
                 */
                if (!in_array($method, array_keys($routeMethodsArray))) {
                    $this->logger->debug("Route '$endpoint' does have method $method");
                    throw new RouterException("Route '$endpoint' does have method $method", 405);
                }

                /**
                 * Extract the function path from the route
                 */
                $function = $routeMethodsArray[$method];

                /**
                 * Key the positional path arguments with the key values set in the route.
                 */
                $pathArgs = array_combine($pathArgKeys, $pathArgVals);

                return [
                    'function' => $function,
                    'path_args' => $pathArgs ?? [],
                ];
            }
        }

        /**
         * HTTP 404
         * No more routes to test.
         */
        $this->logger->debug("Route '$endpoint' not found");
        throw new RouterException("Not Found", 404);
    } // getRoute


    /**
     * Load the route table and set as the lookup array
     * used by getRoute()
     *
     * The lookup array is of the form:
     * [
     *     'endpoint' => [
     *                      'method' => 'function',
     *                      ...
     *                   ]
     *     ...
     * ]
     *
     * @return Array
     */
    private function getRouteTable():array
    {
        /**
         * Get the array of the routes, one route as a line per element
         */
        $routeLines = $this->getRouteLines();

        /**
         * Split each line into an array of keyed tokens
         */
        $routeTokens = array_map(function ($line) {
            $lineTokens = preg_split("!\s+! ", $line);
            return [
                "method" => $lineTokens[0],
                "endpoint" => $lineTokens[1],
                "function" => $lineTokens[2],
            ];
        }, $routeLines);

        /**
         * Key by the endpoint with the values an array of
         * methods keying the associated function.
         */
        $routeTable = [];
        foreach ($routeTokens as $rt) {
            $routeTable[$rt['endpoint']][$rt['method']] = $rt['function'];
        }

        return $routeTable;
    } // getRouteTable


    /**
     * Returns an array of route config lines.
     *
     * The array is initially read from the routes.txt file in the parent project
     * and is then cached in a Swoole Table for faster access on future requests.
     *
     * @return Array
     */
    private function getRouteLines():array
    {
        $routesArray = [];
        foreach ($this->routesLookup->all() as $k => $v) {
            $routesArray[] = $v;
        }
        return array_values($routesArray);
    } // getRouteLines
}
