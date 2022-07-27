<?php
namespace Ghorwood\Tangelo;


define('GREEN', '32');
define('YELLOW', '33');
define('RED', '31');
define('BLUE', '34');
define('CYAN', '36');
define('ESC', "\033");
define('CLOSE_ANSI', ESC."[0m"); //
define('OK', "[".ESC."[".GREEN."mOK".CLOSE_ANSI."] "); // non-standard
define('DEBUG', "[".ESC."[".YELLOW."mDEBUG".CLOSE_ANSI."] ");
define('ERROR', "[".ESC."[".RED."mERROR".CLOSE_ANSI."] ");
define('LOG', "[".ESC."[".CYAN."mLOG".CLOSE_ANSI."] ");
define('TRAFFIC', "[".ESC."[".BLUE."mLOG".CLOSE_ANSI."] ");

class Logger
{
    private Int $verbosity = 1;

    public function __construct()
    {
    }

    public function setVerbosity(?Int $verbosity = null) {
        $this->verbosity = $verbosity;
    }

    public function ok(String $message, Int $verbosity = 1):void
    {
        $verbosity <= $this->verbosity ?  fwrite(STDOUT, OK.$message.PHP_EOL) : null;
    }

    public function debug(String $message, Int $verbosity = 1):void
    {
        $verbosity <= $this->verbosity ?  fwrite(STDOUT, DEBUG.$message.PHP_EOL) : null;
    }

    public function traffic(String $message):void
    {
        fwrite(STDOUT, TRAFFIC.$message.PHP_EOL);
    }

    public function error(String $message, Int $verbosity = 1):void
    {
        fwrite(STDERR, ERROR.$message.PHP_EOL);
    }

    public function log(String $message):void
    {
        fwrite(STDOUT, LOG.$message.PHP_EOL);
    }
}
