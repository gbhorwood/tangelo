<?php
namespace Ghorwood\Tangelo\Lookups;

use Swoole\Http\Table;

use Ghorwood\Tangelo\Logger as Logger;

class RoutesLookup extends Lookup
{
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
     * Reads the routes table and loads inot a Swoole\Table
     *
     * @param  String  $routesFilePath
     * @return void
     */
    public function load(String $routesFilePath):void
    {
        /**
         * Validate routes file routes.txt exists
         */
        if (!file_exists($routesFilePath) || !is_readable($routesFilePath)) {
            $this->logger->error("Routes file not found at $routesFilePath");
            throw new \Exception("Routes file not found at ".$routesFilePath);
        }
        $this->logger->Ok("Routes file routes.txt found at ".$routesFilePath, 1);

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
            $routesDb = $this->createDb(filesize($routesFilePath)*1.2);

            /**
             * set each line in the swoole table
             */
            array_walk($routesArray, function (&$v, $k) use ($routesDb) {
                $routesDb->set($k, ['line' => $v]);
            });

            $this->logger->Ok("Routes file routes.txt loaded into internal db", 1);
            $this->db = $routesDb;
        } catch (Exception $e) {
            $this->logger->error("Could not create routes db: ".$e->getMessage());
            throw new \Exception("Could not create routes db");
        }
    }
}
