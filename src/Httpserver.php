<?php
namespace Ghorwood\Tangelo;

use Swoole\Coroutine;
use Swoole\Http\Table;
use Swoole\Http\Server;
use Swoole\Database\PDOPool;
use Swoole\Database\PDOConfig;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

use Bitty\Http\ServerRequest;
use Bitty\Http\ServerRequestFactory;

use Middleland\Dispatcher;

use Ghorwood\Tangelo\Logger as Logger;
use Ghorwood\Tangelo\ConfigLookup as ConfigLookup;
use Ghorwood\Tangelo\RoutesLookup as RoutesLookup;
use Ghorwood\Tangelo\Exceptions\RouterException as RouterException;


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
        $this->logger = new Logger();

        /**
         * Load config and routes values into Swoole\Table wrapper objects
         * Failure is fatal.
         */
        try {
            $this->logger->welcome();

            $configFilePath = $scriptRoot.DIRECTORY_SEPARATOR.".env";
            $this->configLookup = new ConfigLookup();
            $this->configLookup->load($configFilePath, $this->logger);

            $routesFilePath = $scriptRoot.DIRECTORY_SEPARATOR."routes.txt";
            $this->routesLookup = new RoutesLookup();
            $this->routesLookup->load($routesFilePath, $this->logger);

            $this->logger->setVerbosity(intval($this->configLookup->get('LOGGING_VERBOSITY')));
            $this->logger->setUseColour(intval($this->configLookup->get('LOGGING_USE_COLOUR')));
        }
        catch (\Exception $e) {
            die();
        }

        /**
         * Create a new router with the route and config lookups
         */
        $this->router = new Router($this->routesLookup, $this->configLookup, $this->logger);
    }


    /**
     * Start the server
     *
     * @return  void
     */
    public function run():void
    {
        /**
         * Harvest ip and port of the server from config.
         */
        $serverIp = $this->configLookup->get('SERVER_IP', DEFAULT_SERVER_IP);
        $serverPort = $this->configLookup->get('SERVER_PORT', DEFAULT_SERVER_PORT);

        /**
         * Create and configure Swoole http server
         */
        $http = new Server($serverIp, $serverPort);
        $http->set([
            'max_coroutine' => SERVER_MAX_COROUTINES,
            'enable_coroutine' => true,
        ]);
        $this->logger->ok("Listening on ".$serverIp.":".$serverPort, 1);
        $this->logger->ok("Max coroutines ".SERVER_MAX_COROUTINES, 1);

        /**
         * Handle incoming requests.
         * Cast Swoole request to psr7, create and run the user-defined middleware stack
         * and emit the psr7 response.
         */
        $http->on('Request', function (SwRequest $swRequest, SwResponse $swResponse) {
            $psr7Request = $this->makePsr7Request($swRequest);
            $mw = new Middleware($this->router, $this->configLookup, $this->logger);
            $psr7Response = $mw->run($psr7Request);
            $this->emitPsr7($swResponse, $psr7Response);
        });

        /**
         * Start the Swoole server
         */
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
    } // emitPsr7


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
