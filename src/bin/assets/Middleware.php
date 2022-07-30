<?php
namespace PROJECTNAMESPACE;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Ghorwood\Tangelo\ConfigLookup as ConfigLookup;
use Ghorwood\Tangelo\TangeloMiddleware as TangeloMiddleware;

/**
 * Sample Middleware
 *
 */
class Middleware extends TangeloMiddleware
{

    /**
     * Get list of methods in this class that will run in the middleware.
     * Methods are run in the order they appear.
     */
    public static function stack()
    {
        return [
            'calculateDuration',
        ];
    }


    /**
     * Method that returns a callable to run as middleware.
     * Returned function must accept as parameters a Psr7 request, and an optional
     * callablea. It must return a Psr7 response.
     */
    public function calculateDuration()
    {
        // return the middleware function
        return function (ServerRequestInterface $requestObj, Callable $next = null):ResponseInterface {

            // activity before calling the next middleware, ie modifying the request.
            $start = microtime(true);

            // call handle() on the $next function in the middleware 
            $response = $next->handle($requestObj);

            // activity after calling the next middleware, ie modifying the response
            $duration = (microtime(true) - $start)*1000;

            // access the configuration object to get values cached from your .env
            if ($this->config->get('MIDDLEWARE_INCLUDE_DURATION')) {
                return $response->withHeader('X-Duration', strval($duration)."μs");
            }

            // return a Psr7 response
            return $response;
        };
    }

}