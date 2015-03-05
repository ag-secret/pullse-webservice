<?php
session_start();
error_reporting(E_ALL);

require dirname(__DIR__) . '/config/bootstrap.php';

use Mayhem\Routing\Dispatcher;
use Mayhem\Routing\CORS;

date_default_timezone_set($config['defaultTimezone']);

CORS::getCors();
Dispatcher::dispatch($config);

?>