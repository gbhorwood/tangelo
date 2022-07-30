<?php
namespace Ghorwood\Tangelo\Lookups;

use Swoole\Http\Table;

use Ghorwood\Tangelo\Logger as Logger;

/**
 * Lookup for user-supplied caching
 *
 */
class CacheLookup extends Lookup
{
    private Int $expirySeconds = 5;

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
     * Create the Swoole\Table for caching 
     *
     * @return void
     */
    public function load():void
    {
        try {
            /**
             * Call createDb in superclass
             */
            $configDb = $this->createDb(102400);
            $this->db = $configDb;
            $this->logger->Ok("Cache db created", 1);
        } catch (Exception $e) {
            $this->logger->error("Could not create cache db: ".$e->getMessage());
            throw new \Exception("Could not create cache db");
        }
    }


    /**
     * Store a string value in the cache keyed by the calling method and a unique identifier.
     *
     * @param  String $method     The method calling store(), ie. the output of __METHOD__
     * @param  String $identifier An identifier unique to the record in the method.
     * @param  String $data       The data to store
     * @return bool
     */
    public function store(String $method, String $identifier, String $data):bool
    {
        // @todo make this settable
        $expiryTs = time() + $this->expirySeconds;

        if(!strlen(trim($method)) || !strlen(trim(strval($identifier)))) {
            $this->logger->error("Could not cache on a null key in ".$method);
            return false;
        }

        // we hash the method to reduce the amount of 'key too long' errors
        $key = md5($method)."::".$identifier;

        try {
            $this->db->set($key, ['line' => $data, 'expiry_ts' => $expiryTs]);
            $this->logger->log("Cache: Cached value stored at  $key and expiry {$this->expirySeconds}s in $method", 3);
        }
        catch (\Exception $e) {
            $this->logger->error("Cache: error storing key $key: ".$e->getMessage());
            return false;
        }
        return true;
    }


    /**
     * Retrieve the value from the cache for the key
     *
     * @param  String $method     The method calling store(), ie. the output of __METHOD__
     * @param  String $identifier An identifier unique to the record in the method.
     * @return String or false
     */
    public function retrieve(String $method, String $identifier)
    {
        // we hash the method to reduce the amount of 'key too long' errors
        $key = md5($method)."::".$identifier;

        //$this->logger->log("Cache: trying retrieve of key $key and expiry {$this->expirySeconds}s in $method", 3);
        try {
            $response = $this->db->get($key);

            if(!$response) {
                $this->logger->log("Cache: Cached value missed at  $key in $method", 3);
                return false;
            }

            if(time() > $response['expiry_ts']){
                $this->logger->log("Cache: Cached value expired at $key in $method", 3);
                $this->db->del($key);
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
