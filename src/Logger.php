<?php
namespace Ghorwood\Tangelo;


define('GREEN', '32');
define('YELLOW', '33');
define('RED', '31');
define('ESC', "\033");
define('CLOSE_ANSI', ESC."[0m"); //
define('OK', "[".ESC."[".GREEN."mOK".CLOSE_ANSI."] "); // non-standard
define('DEBUG', "[".ESC."[".YELLOW."mDEBUG".CLOSE_ANSI."] ");
define('ERROR', "[".ESC."[".RED."mERROR".CLOSE_ANSI."] ");

class Logger
{

    public function __construct()
    {
    }

    public function ok(String $message):void
    {
        fwrite(STDOUT, OK.$message.PHP_EOL);
    }

    public function debug(String $message):void
    {
        fwrite(STDOUT, DEBUG.$message.PHP_EOL);
    }

    public function error(String $message):void
    {
        fwrite(STDERR, ERROR.$message.PHP_EOL);
    }
}
