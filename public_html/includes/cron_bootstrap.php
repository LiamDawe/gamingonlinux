<?php
// Load Composer's autoloader
require dirname ( dirname(__FILE__) ) . '/vendor/autoload.php';

require dirname(__FILE__) . "/loader.php";

include (dirname(__FILE__) . '/config.php');

$dbl = new db_mysql();

$core = new core($dbl);
define('url', $core->config('website_url'));