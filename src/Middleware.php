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

    private ConfigLookup $config;

    /**
     * Create the middleware object and set the client middleware stack.
     *
     * @param  Router       $router The \Ghorwood\Tangelo\Router object
     * @param  ConfigLookup $configLookup
     */
    public function __construct(Router $router, ConfigLookup $configLookup)
    {
        $this->router = $router;
        $this->config = $configLookup;

        /**
         * Load all the user-defined middleware functions into an array.
         * We do this by calling the ::stack() function from the user-defined Middleware class
         * and the converting all those strings to closures and putting them in an array.
         *
         * @todo Do a PSR-11 or something instead of whatever this is -gbh
         */
        $mw = NAMESPACE_ROOT."\Middleware";
        $middlewareStackStrings = array_map(fn ($m) => "\\".$mw."::".$m, $mw::stack());
        $this->middleware = array_map(fn ($m) => $m(), $middlewareStackStrings);

        /**
         * Middleware function that runs the actual routed funtion goes at the end
         */
        $this->middleware[] = function ($psr7Request, $next = null):ResponseInterface {
            try {
                // get class.function for this method/endpoint from the router
                $function = $this->router->getRoute($psr7Request->getMethod(), $psr7Request->getUri());

                // get class and method to call from class.method string
                list($className, $method) = explode('.', $function['function']);

                // load, instantiate and call the controller method
                // @todo Do a PSR-11 or something instead of whatever this is -gbh
                $classByNamespace = NAMESPACE_ROOT.'\Controllers\\'.$className;
                if(!class_exists($classByNamespace)) {
                    throw new RouterException("File does not exist", 500);
                }
                $class = new $classByNamespace(
                    $function['path_args'] ?? [],
                    $psr7Request->getQueryParams() ?? [],
                    $this->config
                );

                // run the method
                $controllerResponse = $class->$method();

                return $controllerResponse;
            }
            catch (RouterException $re) {
                // @todo log here
                return new Response(
                    json_encode(['data' => $re->getMessage()]),
                    $re->getHttpCode());
            }
            catch (\Exception $e) {
                // @todo log here
                return new Response(
                    json_encode(['data' => $e->getMessage()]),
                    500);
            }
        };
    } // __construct


    public static function runControllerMethod()
    {
        return function ($psr7Request, $next = null):ResponseInterface {
            try {
                // get class.function for this method/endpoint from the router
                $function = $this->router->getRoute($psr7Request->getMethod(), $psr7Request->getUri());

                // get class and method to call from class.method string
                list($className, $method) = explode('.', $function['function']);

                // load, instantiate and call the controller method
                // @todo Do a PSR-11 or something instead of whatever this is -gbh
                $classByNamespace = NAMESPACE_ROOT.'\Controllers\\'.$className;
                if(!class_exists($classByNamespace)) {
                    throw new RouterException("File does not exist", 500);
                }
                $class = new $classByNamespace(
                    $function['path_args'] ?? [],
                    $psr7Request->getQueryParams() ?? []
                );

                // run the method
                $controllerResponse = $class->$method();

                return $controllerResponse;
            }
            catch (RouterException $re) {
                // @todo log here
                return new Response(
                    json_encode(['data' => $re->getMessage()]),
                    $re->getHttpCode());
            }
            catch (\Exception $e) {
                // @todo log here
                return new Response(
                    json_encode(['data' => $e->getMessage()]),
                    500);
            }
        };
    }


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
