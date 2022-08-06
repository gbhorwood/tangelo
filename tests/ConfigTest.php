<?php
namespace Tests;

use Swoole\Coroutine;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use \Ghorwood\Tangelo\Logger;
use \Ghorwood\Tangelo\Lookups\ConfigLookup;

/**
 * Test CacheLookup
 *
 */
class ConfigTest extends TestCase
{
    private $configFilePath;

    public function setUp():void
    {
        $envText =<<<TXT
        SAMPLE_CONFIG_VALUE=foo
        #COMMENTED_OUT_VALUE=bar
        CONFIG_VALUE_WITH_EQUALS=some=value=foo
        CONFIG_VALUE_WITH_COMMENT=foo  # a comment
        TXT;
        $root = vfsStream::setup();
        $configFile = vfsStream::newFile('config.txt')->at($root);
        $configFile->setContent($envText);
        $this->configFilePath = $configFile->url();
    }

    /**
     * Test store and retrieve
     *
     */
    public function testConfigLookupSuccess()
    {
        \Co\run(function () {
            $stub = $this->getMockBuilder(Logger::class)->setMethods(['ok', 'error', 'log'])->getMock();

            $configLookup = new ConfigLookup($stub);
            $configLookup->load($this->configFilePath);

            $this->assertEquals('foo', $configLookup->get('SAMPLE_CONFIG_VALUE'));
            $this->assertEquals('foo', $configLookup->get('NONEXISTANT_KEY', 'foo'));
            $this->assertEquals('foo', $configLookup->get('CONFIG_VALUE_WITH_COMMENT'));
            $this->assertEquals('some=value=foo', $configLookup->get('CONFIG_VALUE_WITH_EQUALS'));
            $this->assertNotEquals('bar', $configLookup->get('COMMENTED_OUT_VALUE'));
        });
    }
}
