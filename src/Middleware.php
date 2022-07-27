<?php
namespace Ghorwood\Tangelo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

use Bitty\Http\ResponseFactory;
use Bitty\Http\Response;

use Middleland\Dispatcher;

use Ghorwood\Tangelo\Router as Router;
use Ghorwood\Tangelo\Exceptions\RouterException as RouterException;
use Ghorwood\Tangelo\ConfigLookup as ConfigLookup;
use Ghorwood\Tangelo\Logger as Logger;

/**
 * Middleware handler
 *
 * For getting and running user-supplied middleware and running the
 * controller method.
 */
class Middleware
{

    /**
     * Array of closures to run in the middleware
     */
    private array $middleware;

    /**
     * The router object that converts the method/endpoint to a function
     */
    private Router $router;

    /**
     * The config table wrapper for access to .env values
     */
    private ConfigLookup $config;

    /**
     * Logger object
     */
    private Logger $logger;

    /**
     * Create the middleware object and set the client middleware stack.
     *
     * @param  Router       $router The \Ghorwood\Tangelo\Router object
     * @param  ConfigLookup $configLookup
     */
    public function __construct(Router $router, ConfigLookup $configLookup, Logger $logger)
    {
        $this->router = $router;
        $this->config = $configLookup;
        $this->logger = $logger;

        /**
         * Load all the user-defined middleware functions into an array.
         * We do this by calling the ::stack() function from the user-defined Middleware class
         * and the converting all those strings to closures and putting them in an array.
         *
         * @todo Do a PSR-11 or something instead of whatever this is -gbh
         */
        $mw = NAMESPACE_ROOT."\Middleware";
        $mwObj = new $mw($configLookup);
        $this->middleware = array_map(fn($m) => $mwObj->$m(), $mwObj::stack());

        /**
         * Run the controller method
         * Middleware function appended to end of middleware stack that resolves the route,
         * loads the controller class and runs the appropriate method.
         */
        $this->middleware[] = function ($psr7Request, $next = null) :ResponseInterface {
            try {
                /**
                 * Get 'class.method' that this http method/endpoint in the router resolves to
                 * This throws RouterException for 404 and 405 cases.
                 */
                $resolved = $this->router->getRoute($psr7Request->getMethod(), $psr7Request->getUri());
                list($className, $method) = explode('.', $resolved['function']);

                /**
                 * Instantiate the controller class
                 * @todo Do a PSR-11 or something instead of whatever this is -gbh
                 */
                $classByNamespace = NAMESPACE_ROOT.'\Controllers\\'.$className;
                if(!class_exists($classByNamespace)) {
                    throw new RouterException("File does not exist", 500);
                }
                $class = new $classByNamespace(
                    $resolved['path_args'] ?? [],
                    $psr7Request->getQueryParams() ?? [],
                    $this->config
                );

                /**
                 * Call the controller method
                 */
                $controllerResponse = $class->$method();

                /**
                 * Log traffic if configuration is set
                 */
                if($this->config->get('LOGGING_SHOW_TRAFFIC', false)) {
                    $this->logger->traffic(join(' ', [
                        '('.date("Y-m-d H:i:s").')',
                        $psr7Request->getMethod(), 
                        $psr7Request->getUri(), 
                        $controllerResponse->getStatusCode()
                    ]));
                }

                return $controllerResponse;
            }
            /**
             * Catch router exceptions that result in:
             * - 404
             * - 405
             */
            catch (RouterException $re) {
                return new Response(json_encode(['data' => $re->getMessage()]), $re->getHttpCode());
            }
            /**
             * Catch all other exceptions that result in 500
             */
            catch (\Exception $e) {
                return new Response(json_encode(['data' => $e->getMessage()]), 500);
            }
        };
    } // __construct


    /**
     * Run the middleware, return the response to the calling http server
     *
     * @param  ServerRequestInterface $psr7Request
     * @return ResponseInterface
     */
    public function run(ServerRequestInterface $psr7Request):ResponseInterface
    {
        $dispatcher = new Dispatcher($this->middleware);
        $psr7Response = $dispatcher->dispatch($psr7Request);
        return $psr7Response;
    }
}
