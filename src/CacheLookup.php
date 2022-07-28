<?php
namespace Ghorwood\Tangelo;

use Swoole\Http\Table;

use Ghorwood\Tangelo\Logger as Logger;

class CacheLookup 
{
    /**
     * Swoole Table to hold configuration values
     */
    private \Swoole\Table $db;


    /**
     * Default constructor
     */
    public function __construct()
    {
    }


    public function store(String $method, String $identifier, String $data):bool
    {
        $expirySeconds = 60;
        $expiryTs = time() + $expirySeconds;

        if(!strlen(trim($method)) || !strlen(trim(strval($identifier)))) {
            $this->logger->error("Could not cache on a null key in ".$method);
            return false;
        }
        $key = md5($method)."::".$identifier;
        echo strlen($key);
        $this->cache->set($key, ['data' => $data, 'expiry_ts' => $expiryTs]);
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
            $all[] = $v['line'];
        }
        return array_values($all);
    }


    /**
     *
     * @param  Int  $size  The size of the table in bytes
     * @return \Swoole\Table
     */
    private function createDb(Int $size):\Swoole\Table
    {
            $configDb = new \Swoole\Table($size);
            $configDb->column('line', \Swoole\Table::TYPE_STRING, 512);
            $configDb->column('expiry_ts', \Swoole\Table::TYPE_INT, 4);
            $configDb->create();
            return $configDb;
    }
}
