<?php
require dirname(__FILE__) . "/loader.php";
include(dirname(__FILE__) . '/PHPMailer/PHPMailerAutoload.php');

$db_conf = include dirname(__FILE__) . '/config.php';

$db = new mysql($db_conf['host'], $db_conf['username'], $db_conf['password'], $db_conf['database']);
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

$core = new core($dbl);
define('url', $core->config('website_url'));

$message_map = new message_map();

$plugins = new plugins($dbl, $core, APP_ROOT);

$article_class = new article($dbl, $core, $plugins);

$bbcode = new bbcode($dbl, $core, $plugins);
