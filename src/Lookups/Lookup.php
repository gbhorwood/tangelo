<?php
namespace Ghorwood\Tangelo\Lookups;

use Swoole\Http\Table;

use Ghorwood\Tangelo\Logger as Logger;

abstract class Lookup
{
    /**
     * Swoole Table to hold configuration values
     */
    protected \Swoole\Table $db;

    /**
     * Logger
     */
    protected Logger $logger;

    /**
     * Default constructor
     *
     * @param  Logger  $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }


    /**
     * Get one value by it's key with optional default value if not found.
     *
     * @param  String $key
     * @param  String $default  Default value null
     * @return String|Null
     */
    public function get(String $key, String $default = null):?String
    {
        if (!$this->db->exists($key)) {
            return $default;
        }
        return $this->db->get($key, 'line');
    }


    /**
     * Return all values as associative array
     *
     * @return Array
     */
    public function all():array
    {
        $all = [];
        foreach ($this->db as $k => $v) {
            $all[$k] = $v['line'];
        }
        return $all;
    }


    /**
     * Create a new Swoole\Table
     *
     * @param  Int  $size  The size of the table in bytes
     * @return \Swoole\Table
     */
    public function createDb(Int $size):\Swoole\Table
    {
        $configDb = new \Swoole\Table($size);
        $configDb->column('line', \Swoole\Table::TYPE_STRING, 512);
        $configDb->column('expiry_ts', \Swoole\Table::TYPE_INT, 4);
        $configDb->create();
        return $configDb;
    }
}
