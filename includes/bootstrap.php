<?php
$iniGet = ini_get('session.cookie_httponly');

if ($iniGet != "1") 
{
	session_write_close();
	ini_set('session.cookie_httponly', '1');
	session_start();
}

require dirname(__FILE__) . "/loader.php";
include(dirname(__FILE__) . '/PHPMailer/PHPMailerAutoload.php');

include (dirname(__FILE__) . '/config.php');

$dbl = new db_mysql();

$core = new core($dbl);
define('url', $core->config('website_url'));

$message_map = new message_map();

// setup the templating, if not logged in default theme, if logged in use selected theme
$templating = new template($core, $core->config('template'));

$filecache = new file_cache($core);

$user = new user($dbl, $core);

$bbcode = new bbcode($dbl, $core);

$article_class = new article($dbl, $core, $user, $templating, $bbcode);
