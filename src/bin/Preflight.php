<?php
namespace Ghorwood\Tangelo\bin;

include_once(__DIR__.'/../Logger.php');

use \Ghorwood\Tangelo\Logger as Logger;

class Preflight
{
    /**
     * Run all the preflight checks agains this system.
     * All failures are fatal.
     *
     * @return  void
     */
    public static function run()
    {
        $logger = new Logger();

        /**
         * Keep track of pass state so we can display all checks before die()ing
         */
        $pass = true;

        /**
         * Extensions necessary for Tangelo
         */
        $requiredExtensions = [ "pcre",
                                "openswoole",
                                "PDO",
                                "bcmath",
                                "mbstring",
                                "json",
                                "pdo_mysql"
        ];

        /**
         * Run all the preflight checks and harvest the pass state
         */
        $logger->headline("Running preflight");
        $pass = self::checkPhpVersion($logger);
        $pass = self::checkTargetDir($logger);
        foreach ($requiredExtensions as $extension) {
            $pass = self::checkLoadedExtension($extension, $logger);
        }

        /**
         * Failures are fatal.
         */
        if(!$pass) {
            $logger->error("Please address the errors with the system and try again.");
            die();
        }
        $logger->ok("All preflight checks pass");
    } 


    /**
     * Confirm the target install directory is writeable
     *
     * @param Logger $logger
     */
    public static function checkTargetDir(Logger $logger):bool
    {
        $path = realpath(__DIR__.'/../../../../../');
        if(!is_writeable($path)) {
            $logger->error("Target directory $path is not writeable.");
            return false;
        }
        $logger->ok("Target path is writeable");
        return true;
    }


    /**
     * Confirm a given extension is loaded
     *
     * @param Logger $logger
     */
    public static function checkLoadedExtension(String $extension, Logger $logger):bool
    {
        if (!extension_loaded($extension)) {
            $logger->error("Extension $extension must be installed and loaded.");
            return false;
        }
        $logger->ok("Found extension $extension");
        return true;
    }


    /**
     * Confirm PHP minimum 7.4. This is a hardcoded value. 
     *
     * @param Logger $logger
     */
    public static function checkPhpVersion(Logger $logger):bool
    {
        $phpversion_array = explode('.', phpversion());
        if ((int)$phpversion_array[0].$phpversion_array[1] < 74) {
            $logger->error("PHP must be version 7.4 or higher");
            return false;
        }
        $logger->ok("PHP version ".$phpversion_array[0].'.'.$phpversion_array[1]);
        return true;
    }
}
