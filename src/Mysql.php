<?php
namespace Ghorwood\Tangelo;

use Swoole\Http\Table;
use Swoole\Database\PDOPool;
use Swoole\Database\PDOConfig;

use Ghorwood\Tangelo\Lookups\ConfigLookup as ConfigLookup;
use Ghorwood\Tangelo\Logger as Logger;

/**
 * Database access object
 *
 */
class Mysql
{
    /**
     * Lookup wrapping Swoole\Tanble of config data
     */
    private ConfigLookup $config;

    /**
     * The Swoole PDO pool
     */
    private ?PDOPool $pdoPool = null;

    /**
     * The logger
     */
    private Logger $logger;


    /**
     * Constructor
     *
     * @param  ConfigLookup $config
     * @param  Logger       $logger
     * @return void
     */
    public function __construct(ConfigLookup $config, Logger $logger)
    {
        $this->logger = $logger;
        $this->config = $config;
    }


    /**
     * Connect to the db and build a PDO pool
     *
     * @return void
     */
    private function connect()
    {
        try {
            /**
             * Build PDO config using configuration values from ConfigLookup
             */
            $pdoConfig = (new PDOConfig())->withHost($this->config->get('DB_HOST'))
                                      ->withPort($this->config->get('DB_PORT'))
                                      ->withCharset('utf8mb4')
                                      ->withDbName($this->config->get('DB_NAME'))
                                      ->withUsername($this->config->get('DB_USER'))
                                      ->withPassword($this->config->get('DB_PASS'));

            $this->logger->Ok("Connecting to Mysql at " .
                $this->config->get("DB_HOST") .
                ":" .
                $this->config->get("DB_PORT").
                " db ".
                $this->config->get("DB_NAME"));

            /**
             * Create PDO Pool.
             */
            $this->pdoPool = new PDOPool($pdoConfig);
            $this->pdoPool->get();
            $this->logger->Ok("Connected to Mysql ");
        } catch (\Exception $e) {
            $this->logger->error("Failed connecting to Mysql ".$e->getMessage());
            throw $e;
        }
    } // connect


    /**
     * Run a query and return the results.
     * On first run, connects to the database
     *
     * @param  String $sql          The sql for the prepared statement
     * @param  Array  $parameters   The optional paramteters for the statement
     */
    public function query(String $sql, array $parameters = []):array
    {
        try {
            $start = microtime(true);

            /**
             * If this is the first query, connect.
             */
            if (!$this->pdoPool) {
                $this->connect();
            }

            /**
             * Execute the statement
             */
            $pdo = $this->pdoPool->get();
            $statement = $pdo->prepare($sql);
            $statement->execute($parameters);
            $result = $statement->fetchall(\PDO::FETCH_ASSOC);

            /**
             * Logging and verbosity 3
             */
            $uniqid = uniqid();
            $this->logger->log('Mysql ('.$uniqid.'): '.$this->formatSql($sql), 3);
            $this->logger->log('Mysql ('.$uniqid.'): Success. Duration: '.((microtime(true) - $start) * 1000).'μs', 3);

            $statement = null;
            return $result;
        } catch (\PDOException $e) {
            $this->logger->error($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    } // query


    /**
     * Format sql into a single line for logging
     *
     * @param  String $sql The sql statement to format
     * @return String
     */
    private function formatSql(String $sql):String
    {
        return preg_replace('!\s+!', ' ', preg_replace('![\r\n]!', ' ', $sql));
    }
}
