<?php
namespace Ghorwood\Tangelo\bin;

include_once(__DIR__.'/../Logger.php');

use \Ghorwood\Tangelo\Logger as Logger;

class Setup
{
    public static function run()
    {
        $logger = new Logger();
        $pass = true;

        $logger->headline("Running setup");
        $projectDir = self::getProjectDir($logger);
        $nameSpace = self::guessNamespace($projectDir, $logger);

        $pass = self::copySampleFile('.env', $projectDir, $nameSpace, $logger);
        $pass = self::copySampleFile('routes.txt', $projectDir, $nameSpace, $logger);

        $pass = self::writeSampleFileWithNamespace('Middleware.php', $projectDir, $nameSpace, $logger);
        $pass = self::writeSampleFileWithNamespace('run.php', $projectDir, $nameSpace, $logger);
        $pass = self::createDirectory('Controllers', $projectDir, $nameSpace, $logger);
        $pass = self::writeSampleFileWithNamespace('SampleController.php', $projectDir.'/Controllers/', $nameSpace, $logger);


        /**
         * Failures are not fatal.
         */
        if (!$pass) {
            $logger->error("There were errors setting up the project. Please review.");
        } else {
            $logger->ok("System setup done");
        }
    }

    public static function createDirectory(String $directoryName, String $projectDir, String $nameSpace, Logger $logger):bool
    {
        $destDir = $projectDir.'/'.$directoryName;
        if (file_exists($destDir)) {
            $logger->error("Destination directory '$directoryName' already exists, NOT overwriting.");
            return false;
        }

        $success = mkdir($destDir, 0755);
        if(!$success) {
            $logger->error("Could not create directory '$directoryName'.");
            return false;
        }

        $logger->ok("Created sample '$destDir' directory");
        return true;
    }


    public static function writeSampleFileWithNamespace(String $fileName, String $projectDir, String $nameSpace, Logger $logger):bool
    {
        $sourceFile =  __DIR__.'/assets/'.$fileName;
        $destFile = $projectDir.'/'.$fileName;

        if (file_exists($destFile)) {
            $logger->error("Destination '$fileName' already exists, NOT overwriting.");
            return false;
        }

        $sourceLinesArray = file($sourceFile);
        $namespacedLinesArray = array_map(fn ($l) => preg_replace('!PROJECTNAMESPACE!', $nameSpace, $l), $sourceLinesArray);

        $fp = fopen($destFile, 'a');
        if(!$fp) {
            $logger->error("Could not write to destination '$fileName'.");
            return false;
        }
        array_map(function($l) use($fp) {
            fwrite($fp, $l);
        }, $namespacedLinesArray);

        $logger->ok("Installed sample '$fileName' file");
        return true;
    }


    public static function copySampleFile(String $fileName, String $projectDir, String $nameSpace, Logger $logger):bool
    {
        $sourceFile =  __DIR__.'/assets/'.$fileName;
        $destFile = $projectDir.'/'.$fileName;

        if (file_exists($destFile)) {
            $logger->error("Destination '$fileName' already exists, NOT overwriting.");
            return false;
        }

        if (!@copy($sourceFile, $destFile)) {
            $logger->error("Could not copy sample '$fileName' file. Continuing.");
            return false;
        }
        $logger->ok("Installed sample '$fileName' file");
        return true;
    }


    public static function createControllers(String $nameSpace, Logger $logger):bool
    {
    }

    public static function createMiddleware(String $nameSpace, Logger $logger):bool
    {
    }

    public static function createRunner(String $nameSpace, Logger $logger):bool
    {
    }

    public static function guessNamespace(String $projectDir, Logger $logger):String
    {
        $composer = @json_decode(@file_get_contents($projectDir.'/composer.json'));
        $name = @$composer->name;

        if ($name) {
            $nameArray = explode('/', $name);
            $nameSpace = join('\\', array_map(fn ($n) => ucfirst($n), $nameArray));
        } else {
            $nameSpace = get_current_user().'\\'.'Tangeloproject';
        }

        $logger->ok("Guessing namespace as '$nameSpace'");
        return $nameSpace;
    }

    public static function getProjectDir(Logger $logger):String
    {
        $projectDir = realpath(__DIR__.'/../../../../../');
        $logger->ok("Guessing project directory as '$projectDir'");
        return $projectDir;
    }
}
