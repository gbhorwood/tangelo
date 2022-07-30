#!/usr/bin/env php
<?php

include_once(__DIR__.'/Setup.php');
include_once(__DIR__.'/Preflight.php');
include_once(__DIR__.'/../Logger.php');

use \Ghorwood\Tangelo\bin\Setup;
use \Ghorwood\Tangelo\bin\Preflight;
use \Ghorwood\Tangelo\Logger as Logger;

$logger = new Logger();
$logger->welcome();

Preflight::run();

Setup::run();
