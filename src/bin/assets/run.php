<?php
namespace PROJECTNAMESPACE;

include_once('./vendor/autoload.php');

use \Ghorwood\Tangelo\Httpserver;
#use \Ghorwood\Fancyproject\Controllers\TestController;

$srv = new Httpserver(__DIR__, __NAMESPACE__);
$srv->run();
