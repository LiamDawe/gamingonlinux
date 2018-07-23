<?php
require dirname(__FILE__) . "/loader.php";
require dirname(__FILE__) . '/PHPMailer/src/PHPMailer.php';
require dirname(__FILE__) . '/PHPMailer/src/Exception.php';

include (dirname(__FILE__) . '/config.php');

$dbl = new db_mysql();

$core = new core($dbl);
define('url', $core->config('website_url'));

$message_map = new message_map();

// setup the templating, if not logged in default theme, if logged in use selected theme
$templating = new template($core, $core->config('template'));

$filecache = new file_cache($core);

$user = new user($dbl, $core);

$bbcode = new bbcode($dbl, $core, $user);

$article_class = new article($dbl, $core, $user, $templating, $bbcode);
