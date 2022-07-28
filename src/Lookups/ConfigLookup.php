<?php
namespace Ghorwood\Tangelo\Lookups;

use Swoole\Http\Table;

use Ghorwood\Tangelo\Logger as Logger;

class ConfigLookup extends Lookup
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
     * Reads config key values from .env text file and inserts into Swoole\Table
     *
     * @param  String  $configFilePath
     * @return void
     */
    public function load(String $configFilePath):void
    {
        /**
         * Validate config file .env exists
         */
        if (!file_exists($configFilePath) || !is_readable($configFilePath)) {
            $this->logger->error("Config file not found at $configFilePath");
            throw new \Exception("Config file not found at ".$configFilePath);
        }
        $this->logger->Ok("Configuration file .env found at ".$configFilePath, 1);

        try {
            /**
             * Strip out comments and empty lines from routes file. set as array of lines.
             */
            $configsArray = array_values(
                array_filter(
                    array_map(fn ($line) => trim(preg_replace('!#.*$!', null, $line)), file($configFilePath))
                )
            );

            /**
             * Convert to array keyed by value to left of =
             */
            $configsKeyedArray = [];
            foreach ($configsArray as $configLine) {
                $configLineTokens = explode('=', $configLine);
                // values may contain = characters. handle that.
                $configsKeyedArray[array_shift($configLineTokens)] = trim(join('=', $configLineTokens));
            }

            /**
             * create swoole table
             */
            $configDb = $this->createDb(filesize($configFilePath)*1.2);

            /**
             * set each line in the swoole table
             */
            array_walk($configsKeyedArray, function (&$v, $k) use ($configDb) {
                $configDb->set($k, ['line' => $v]);
            });

            $this->logger->Ok("Configuration file .env loaded into internal db", 1);
            $this->db = $configDb;
        } catch (Exception $e) {
            $this->logger->error("Could not create config db: ".$e->getMessage());
            throw new \Exception("Could not create config db");
        }
    }
}
