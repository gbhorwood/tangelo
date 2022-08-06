<?php
namespace Tests;

use Swoole\Coroutine;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use \Ghorwood\Tangelo\Logger;
use \Ghorwood\Tangelo\Router;
use \Ghorwood\Tangelo\Lookups\ConfigLookup;
use \Ghorwood\Tangelo\Lookups\RoutesLookup;
use Ghorwood\Tangelo\Exceptions\RouterException as RouterException;

/**
 * Test CacheLookup
 *
 */
class RouterTest extends TestCase
{
    private $configFilePath;
    private $routesFilePath;
    private $router;

    public function setUp():void
    {
        $root = vfsStream::setup();

        $envText =<<<TXT
        SAMPLE_CONFIG_VALUE=foo
        #COMMENTED_OUT_VALUE=bar
        CONFIG_VALUE_WITH_EQUALS=some=value=foo
        CONFIG_VALUE_WITH_COMMENT=foo  # a comment
        TXT;
        $configFile = vfsStream::newFile('config.txt')->at($root);
        $configFile->setContent($envText);
        $this->configFilePath = $configFile->url();

        $routeText =<<<TXT
        GET  /things/{id}               SampleController.getThing             # handle path parameters example
        GET  /things/{id}/stuff/{sid}   SampleController.getStuffThings       # handle path parameters example
        POST /things                    SampleController.postThing            # handle json body example
        TXT;

        $routesFile = vfsStream::newFile('routes.txt')->at($root);
        $routesFile->setContent($routeText);
        $this->routesFilePath = $routesFile->url();

        $stub = $this->getMockBuilder(Logger::class)->setMethods(['ok', 'error', 'log'])->getMock();

        $configLookup = new ConfigLookup($stub);
        $configLookup->load($this->configFilePath);

        $routerLookup = new RoutesLookup($stub);
        $routerLookup->load($this->routesFilePath);

        $this->router = new Router($routerLookup, $configLookup, $stub);
    }

    /**
     * Test one path id
     *
     */
    public function testRouterOnePathId()
    {
        \Co\run(function () {

            $result = $this->router->getRoute('GET', '/things/12');
            $this->assertEquals('SampleController.getThing', $result['function']);
            $this->assertEquals('12', $result['path_args']['id']);

        });
    }

    /**
     * Test one plus path id
     *
     */
    public function testRouterOnePlusPathId()
    {
        \Co\run(function () {

            $result = $this->router->getRoute('GET', '/things/12/stuff/23');
            $this->assertEquals('SampleController.getStuffThings', $result['function']);
            $this->assertEquals('12', $result['path_args']['id']);
            $this->assertEquals('23', $result['path_args']['sid']);
        });
    }

    /**
     * Test 404
     *
     */
    public function testRouter404()
    {
        \Co\run(function () {
            try {
                $result = $this->router->getRoute('GET', '/nonexistant');
            }
            catch (RouterException $re) {
                $this->assertEquals('Not Found', $re->getMessage());
            }
        });
    }

    /**
     * Test 405
     *
     */
    public function testRouter405()
    {
        \Co\run(function () {
            try {
                $result = $this->router->getRoute('PATCH', '/things/12');
            }
            catch (RouterException $re) {
                $this->assertEquals("Route '/things/12' does have method PATCH", $re->getMessage());
            }
        });
    }

}
