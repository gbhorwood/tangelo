<?php
namespace Ghorwood\Tangelo;

use Swoole\Http\Table;

use Ghorwood\Tangelo\Logger as Logger;

class ConfigLookup 
{
    private \Swoole\Table $db;

    public function __construct()
    {
    }

    /**
     * Reads config key values from .env text file and inserts into Swoole\Table
     *
     * @param  String  $configFilePath
     * @param  Logger  $logger
     * @return void
     */
    public function load(String $configFilePath, Logger $logger):void
    {
        /**
         * Validate config file .env exists
         */
        if (!file_exists($configFilePath) || !is_readable($configFilePath)) {
            $logger->error("Config file not found at $configFilePath");
            throw new \Exception("Config file not found at ".$configFilePath);
        }
        $logger->Ok("Configuration file .env found at ".$configFilePath);

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
            foreach($configsArray as $configLine) {
                $configLineTokens = explode('=',$configLine);
                // values may contain = characters. handle that.
                $configsKeyedArray[array_shift($configLineTokens)] = trim(join('=', $configLineTokens));
            }

            /**
             * create swoole table
             */
            $configDb = new \Swoole\Table(filesize($configFilePath)*1.2);
            $configDb->column('line', \Swoole\Table::TYPE_STRING, 512);
            $configDb->create();

            /**
             * set each line in the swoole table
             */
            array_walk($configsKeyedArray, function (&$v, $k) use ($configDb) {
                $configDb->set($k, ['line' => $v]);
            });

            $logger->Ok("Configuration file .env loaded into internal db");
            $this->db = $configDb;
        } catch (Exception $e) {
            $logger->error("Could not create config db: ".$e->getMessage());
            throw new \Exception("Could not create config db");
        }
    }


    public function get(String $key, String $default = null):String
    {
        if(!$this->db->exists($key)) {
            return $default;
        }
        return $this->db->get($key, 'line');
    }


    public function all():Array
    {
        $all = [];
        foreach ($this->db as $k => $v) {
            $all[] = $v['line'];
        }
        return array_values($all);
    }
}