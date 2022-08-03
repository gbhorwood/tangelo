<?php
namespace Tests;

use Swoole\Coroutine;
use PHPUnit\Framework\TestCase;
use \Ghorwood\Tangelo\Logger;
use \Ghorwood\Tangelo\Lookups\CacheLookup;

/**
 * Test CacheLookup
 *
 */
class CacheTest extends TestCase
{

    /**
     * Test store and retrieve
     *
     * @dataProvider cacheLookupSuccessProvider
     */
    public function testCacheLookupSuccess($method, $id, $data, $expiry)
    {
        \Co\run(function() use ($method, $id, $data, $expiry)
        {
            $stub = $this->getMockBuilder(Logger::class)->setMethods(['ok', 'error', 'log'])->getMock();

            $cacheLookup = new CacheLookup($stub);
            $cacheLookup->load();

            $cacheLookup->store($method, $id, $data, $expiry);
            $this->assertEquals($data, $cacheLookup->retrieve($method, $id));
        });
    }

    /**
     * Test Expired
     *
     */
    public function testCacheLookupExpiry()
    {
        \Co\run(function()
        {
            $stub = $this->getMockBuilder(Logger::class)->setMethods(['ok', 'error', 'log'])->getMock();

            $cacheLookup = new CacheLookup($stub);
            $cacheLookup->load();

            $cacheLookup->store('METHOD', '1', 'somedata', 0);
            sleep(1); // wait one second for cache to expire
            $this->assertEquals(false, $cacheLookup->retrieve('METHOD', '1'));
        });
    }

    /**
     * Test Bad Key
     *
     */
    public function testCacheLookupBadKey()
    {
        \Co\run(function()
        {
            $stub = $this->getMockBuilder(Logger::class)->setMethods(['ok', 'error', 'log'])->getMock();

            $cacheLookup = new CacheLookup($stub);
            $cacheLookup->load();

            $cacheLookup->store('METHOD', '1', 'somedata', 0);
            $this->assertEquals(false, $cacheLookup->retrieve('METHOD', '2'));
        });
    }

    /**
     * Data provider for testCacheLookupSuccess
     *
     */
    public function cacheLookupSuccessProvider()
    {
        return [
            ['testmethod', '1', 'testdata 1', 60],
            ['testmethod', '2', 'testdata 2', 100000],
        ];
    }
}
