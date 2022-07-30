<?php
namespace Ghorwood\Tangelo;

/**
 * Colors and levels constants
 */
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
define('TRAFFIC', "[".ESC."[".BLUE."mTRAFFIC".CLOSE_ANSI."] ");
define('BOLD_ANSI', ESC."[1m");
define('LOG_PLAIN', "[LOG] ");
define('OK_PLAIN', "[OK] ");
define('TRAFFIC_PLAIN', "[TRAFFIC] ");
define('ERROR_PLAIN', "[ERROR] ");
define('DEBUG_PLAIN', "[DEBUG] ");

/**
 * Basic logging to STDOUT and STDERR.
 *
 */
class Logger
{

    /**
     * Verbosity level, default 1 if not set in .env
     */
    private Int $verbosity = 1;

    /**
     * Colour output flag, default true
     */
    private bool $useColour = true;


    /**
     * Default constructor
     */
    public function __construct()
    {
    }


    /**
     * Prints a short welcome message.
     * Called in Httpserver.
     */
    public function welcome():void
    {
        fwrite(STDOUT, "🍊".BOLD_ANSI."tangelo ".CLOSE_ANSI."[".trim(@file_get_contents(__DIR__.'/../version'))."]".PHP_EOL.PHP_EOL);
    }

    /**
     * Set the verbosity level. Called in Httpserver.
     *
     * @param  Int $verbosity
     * @return void
     */
    public function setVerbosity(?Int $verbosity = null)
    {
        $this->verbosity = $verbosity;
    }


    /**
     * Set the use of color in output flag. Called in Httpserver.
     *
     * @param  Int $useColour
     * @return void
     */
    public function setUseColour(Int $useColour = 1)
    {
        $this->useColour = (bool)$useColour;
    }


    /**
     * Output 'OK' message
     *
     * @param  String $message
     * @param  Int    $verbosity  If this value is less or equal to configured verbosity, output.
     * @return void
     */
    public function ok(String $message, Int $verbosity = 1):void
    {
        $level = $this->useColour ? OK : OK_PLAIN;
        $verbosity <= $this->verbosity ?  fwrite(STDOUT, $level.$message.PHP_EOL) : null;
    }


    /**
     * Output 'DEBUG' message
     *
     * @param  String $message
     * @param  Int    $verbosity  If this value is less or equal to configured verbosity, output.
     * @return void
     */
    public function debug(String $message, Int $verbosity = 1):void
    {
        $level = $this->useColour ? DEBUG : DEBUG_PLAIN;
        $verbosity <= $this->verbosity ?  fwrite(STDOUT, $level.$message.PHP_EOL) : null;
    }


    /**
     * Output 'TRAFFIC' message
     *
     * @param  String $message
     * @return void
     */
    public function traffic(String $message):void
    {
        $level = $this->useColour ? TRAFFIC : TRAFFIC_PLAIN;
        fwrite(STDOUT, $level.$message.PHP_EOL);
    }


    /**
     * Output 'LOG' message
     *
     * @param  String $message
     * @return void
     */
    public function log(String $message):void
    {
        $level = $this->useColour ? LOG : LOG_PLAIN;
        fwrite(STDOUT, $level.$message.PHP_EOL);
    }


    /**
     * Output 'ERROR' message.
     * Error is sent to STDERR
     *
     * @param  String $message
     * @return void
     */
    public function error(String $message):void
    {
        $level = $this->useColour ? ERROR : ERROR_PLAIN;
        fwrite(STDERR, $level.$message.PHP_EOL);
    }


    /**
     * Output one line of bold text
     *
     * @param  String $message
     * @return void
     */
    public function headline(String $message):void
    {
        fwrite(STDOUT, BOLD_ANSI.$message.CLOSE_ANSI.PHP_EOL);
    }
}
