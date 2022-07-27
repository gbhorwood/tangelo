<?php
namespace Ghorwood\Tangelo;

use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Http\Table;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;
use Swoole\Database\PDOPool;
use Swoole\Database\PDOConfig;

use Ghorwood\Tangelo\Config as Config;
use Ghorwood\Tangelo\Router as Router;
use Ghorwood\Tangelo\Logger as Logger;
use Ghorwood\Tangelo\Exceptions\RouterException as RouterException;

use Ghorwood\Tangelo\ConfigLookup as ConfigLookup;
use Ghorwood\Tangelo\RoutesLookup as RoutesLookup;

use Bitty\Http\ServerRequest;
use Bitty\Http\ServerRequestFactory;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Middleland\Dispatcher;

define('DEFAULT_SERVER_IP', '127.0.0.2');
define('DEFAULT_SERVER_PORT', 9501);
define('SERVER_MAX_COROUTINES', 10000);

class Httpserver
{
    private Router $router;
    private ConfigLookup $configLookup;
    private RoutesLookup $routesLookup;
    private Logger $logger;

    public function __construct(String $scriptRoot, String $namespaceRoot)
    {
        /**
         * Create logger
         */
        $this->logger = new Logger();

        /**
         * Load config and routes values into Swoole\Table
         */
        try {
            $configFilePath = $scriptRoot.DIRECTORY_SEPARATOR.".env";
            $this->configLookup = new ConfigLookup();
            $this->configLookup->load($configFilePath, $this->logger);

            $routesFilePath = $scriptRoot.DIRECTORY_SEPARATOR."routes.txt";
            $this->routesLookup = new RoutesLookup();
            $this->routesLookup->load($routesFilePath, $this->logger);
        }
        catch (\Exception $e) {
            die();
        }

        /**
         * Create a new router with the route db
         */
        $this->router = new Router($this->routesLookup, $this->configLookup, $this->logger);
    }


    public function run():void
    {

        $serverIp = $this->configLookup->get('SERVER_IP', DEFAULT_SERVER_IP);
        $serverPort = $this->configLookup->get('SERVER_PORT', DEFAULT_SERVER_PORT);

        $http = new Server($serverIp, $serverPort);

        $http->set([
            'max_coroutine' => SERVER_MAX_COROUTINES,
            'enable_coroutine' => true,
        ]);

        $this->logger->ok("Listening on ".$serverIp.":".$serverPort);
        $this->logger->ok("Max coroutines ".SERVER_MAX_COROUTINES);

        $http->on('Request', function (SwRequest $swRequest, SwResponse $swResponse) {
            $psr7Request = $this->makePsr7Request($swRequest);
            $mw = new Middleware($this->router, $this->configLookup);
            $psr7Response = $mw->run($psr7Request);
            $this->emitPsr7($swResponse, $psr7Response);
        });

        $http->start();
    } // run


    /**
     * Emits a Psr7 Response through the \Swoole\Response end() method.
     *
     * @param  \Swoole\Response  $swResponse
     * @param  ResponseInterface $psr7Response
     * @return void
     */
    private function emitPsr7(SwResponse $swResponse, ResponseInterface $psr7Response)
    {
        $swResponse->status($psr7Response->getStatusCode());
        foreach ($psr7Response->getHeaders() as $k => $v) {
            $swResponse->header($k, $v[0]);
        }
        $swResponse->end($psr7Response->getBody());
    }


    /**
     * Converts Swoole Request to a Psr7 Request.
     *
     * @param  \Swoole\Request $request
     * @return ServerRequestInterface
     */
    private function makePsr7Request(SwRequest $request):ServerRequestInterface
    {
        $factory = new ServerRequestFactory();
        $psr7Request = $factory->createServerRequest($request->getMethod(), $request->server['request_uri'] ?? '/');
        $psr7Request = $psr7Request->withQueryParams($request->get);
        return $psr7Request;
    } // makePsr7Request
}
