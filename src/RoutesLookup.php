<?php
namespace Ghorwood\Tangelo;

use Swoole\Http\Table;

use Ghorwood\Tangelo\Logger as Logger;

class RoutesLookup
{
    /**
     * Swoole Table to hold route values
     */
    private \Swoole\Table $db;


    /**
     * Default constructor
     */
    public function __construct()
    {
    }


    /**
     * Reads the routes table and loads inot a Swoole\Table
     *
     * @param  String  $routesFilePath
     * @param  Logger  $logger
     * @return void
     */
    public function load(String $routesFilePath, Logger $logger):void
    {
        /**
         * Validate routes file routes.txt exists
         */
        if (!file_exists($routesFilePath) || !is_readable($routesFilePath)) {
            $logger->error("Routes file not found at $routesFilePath");
            throw new \Exception("Routes file not found at ".$routesFilePath);
        }
        $logger->Ok("Routes file routes.txt found at ".$routesFilePath, 1);

        try {
            /**
             * Strip out comments and empty lines from routes file. set as array of lines.
             */
            $routesArray = array_values(
                array_filter(
                    array_map(fn ($line) => trim(preg_replace('!#.*$!', null, $line)), file($routesFilePath))
                )
            );

            /**
             * create swoole table
             */
            $routesDb = new \Swoole\Table(filesize($routesFilePath)*1.2);
            $routesDb->column('line', \Swoole\Table::TYPE_STRING, 512);
            $routesDb->create();

            /**
             * set each line in the swoole table
             */
            array_walk($routesArray, function (&$v, $k) use ($routesDb) {
                $routesDb->set($k, ['line' => $v]);
            });

            $logger->Ok("Routes file routes.txt loaded into internal db", 1);
            $this->db = $routesDb;
        } catch (Exception $e) {
            $logger->error("Could not create routes db: ".$e->getMessage());
            throw new \Exception("Could not create routes db");
        }
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
}
