<?php
namespace Ghorwood\Tangelo\Lookups;

use Swoole\Http\Table;

use Ghorwood\Tangelo\Logger as Logger;

class CacheLookup extends Lookup
{
    private Int $expirySeconds = 60;

    /**
     * Default constructor
     *
     * @param  Logger  $logger
     */
    public function __construct(Logger $logger)
    {
        parent::__construct($logger);
    }


    /**
     * 
     *
     * @return void
     */
    public function load():void
    {
        try {
            /**
             * create swoole table
             */
            $configDb = $this->createDb(102400);
            $this->db = $configDb;
            $this->logger->Ok("Cache db created", 1);
        } catch (Exception $e) {
            $this->logger->error("Could not create cache db: ".$e->getMessage());
            throw new \Exception("Could not create cache db");
        }
    }



    public function store(String $method, String $identifier, String $data):bool
    {
        $expiryTs = time() + $this->expirySeconds;

        if(!strlen(trim($method)) || !strlen(trim(strval($identifier)))) {
            $this->logger->error("Could not cache on a null key in ".$method);
            return false;
        }
        $key = md5($method)."::".$identifier;

        try {
            $this->db->set($key, ['line' => $data, 'expiry_ts' => $expiryTs]);
            $this->logger->log("Cache: Stored with key $key and expiry {$this->expirySeconds}s in $method", 3);
        }
        catch (\Exception $e) {
            $this->logger->error("Cache: error storing key $key: ".$e->getMessage());
            return false;
        }
        return true;
    }


    public function retrieve(String $method, String $identifier)
    {
        $key = md5($method)."::".$identifier;
        $this->logger->log("Cache: trying retrieve of key $key and expiry {$this->expirySeconds}s in $method", 3);
        try {
            $response = $this->db->get($key);
            if(!$response) {
                $this->logger->log("Cache: No cached value at $key in $method", 3);
                return false;
            }
            return $response['line'];
        }
        catch (\Exception $e) {
            $this->logger->error("Cache: error retrieving $key: ".$e->getMessage());
            return false;
        }
        return "";
    }
}
