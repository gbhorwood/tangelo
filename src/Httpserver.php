<?php
namespace Ghorwood\Tangelo;

use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Http\Table;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;
use Swoole\Database\PDOPool;
use Swoole\Database\PDOConfig;

use Ghorwood\Tangelo\Router as Router;
use Ghorwood\Tangelo\Exceptions\RouterException as RouterException;

use Bitty\Http\ServerRequest;
use Bitty\Http\ServerRequestFactory;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Middleland\Dispatcher;

if (!defined('SCRIPT_ROOT')) {
    define('SCRIPT_ROOT', realpath(__DIR__ .
        DIRECTORY_SEPARATOR.'..'.
        DIRECTORY_SEPARATOR.'..'.
        DIRECTORY_SEPARATOR.'..'.
        DIRECTORY_SEPARATOR.'..'.
        DIRECTORY_SEPARATOR));
}


class Httpserver
{
    private Router $router;
    private ?\Swoole\Table $routesDb = null;

    public function __construct(String $scriptRoot, String $namespaceRoot)
    {
        print "$scriptRoot".PHP_EOL;
        /**
         * Read the route table into a Swoole Table
         */
        $routerFilePath = $scriptRoot.DIRECTORY_SEPARATOR."routes.txt";

        // error on config file unavailable
        if (!file_exists($routerFilePath) || !is_readable($routerFilePath)) {
            // @todo output logger
            throw new \Exception("Route table not found at ".$routerFilePath);
        }

        // strip out comments and empty lines from routes file. set as array of lines.
        $routesArray = array_values(
            array_filter(
                array_map(fn ($line) => trim(preg_replace('!#.*$!', null, $line)), file($routerFilePath))
            )
        );

        $routesDb = self::createRoutesDb(filesize($routerFilePath));

        // set each line in the swoole table
        array_walk($routesArray, function (&$v, $k) use ($routesDb) {
            $routesDb->set($k, ['line' => $v]);
        });

        $this->router = new Router($routesDb);

    }

    public static function createRoutesDb($size)
    {
        try {
            $routesDb = new \Swoole\Table($size);
            $routesDb->column('line', \Swoole\Table::TYPE_STRING, 512);
            $routesDb->create();
            return $routesDb;
        } catch (Exception $e) {
            throw new \Exception("Could not create routes table");
        }
    }


    public function run():void
    {
        $http = new Server("127.0.0.1", 9501);

        $http->set([
            'max_coroutine' => 10000,
            'enable_coroutine' => true,
        ]);



        $http->on('Request', function (SwRequest $swRequest, SwResponse $swResponse) {

            try {
                $psr7Request = $this->makeRequest($swRequest);
                $mw = new Middleware($this->router);
                $psr7Response = $mw->run($psr7Request);
                $this->emitPsr7($swResponse, $psr7Response);
            } catch (RouterException $re) {
                // do we get here?
                print "CATCH!!!!!!!!!!!";
                $this->emit($swResponse, $re->getHttpCode(), $re->getMessage());
            }
        });

        $http->start();
    }


    private function emit(SwResponse $swResponse, Int $code, ?String $data, ?array $extraHeaders = null):void
    {
        $swResponse->status($code);
        $swResponse->header("Content-Type", "application/json");
        $swResponse->end(json_encode($data));
    }


    private function emitPsr7(SwResponse $swResponse, ResponseInterface $psr7Response)
    {

        $swResponse->status($psr7Response->getStatusCode());
        foreach ($psr7Response->getHeaders() as $k => $v) {
            $swResponse->header($k, $v[0]);
        }
        $swResponse->end($psr7Response->getBody());
    }

    /**
     *
     */
    private function parseRequest(Request $request):object
    {
        return  (object)[
        'method' => $request->getMethod(),
        'uri' => $request->server['request_uri'] ?? '/',
        'query_string' => $request->get ?? [],
        'headers' => $request->header,
        'json' => @$request->header['content-type'] == "application/json" ? $request->rawContent() : null,
        ];
    }






    
    private function makeRequest(SwRequest $request):ServerRequestInterface
    {
        $factory = new ServerRequestFactory();
        $psr7Request = $factory->createServerRequest($request->getMethod(), $request->server['request_uri'] ?? '/');
        $psr7Request = $psr7Request->withQueryParams($request->get);
        return $psr7Request;
    }
}
