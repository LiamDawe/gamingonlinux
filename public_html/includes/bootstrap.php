<?php
// Load Composer's autoloader
require dirname ( dirname(__FILE__) ) . '/vendor/autoload.php';

require dirname(__FILE__) . "/loader.php";
include (dirname(__FILE__) . '/config.php');

$dbl = new db_mysql();

$core = new core($dbl);
define('url', $core->config('website_url'));

$user = new user($dbl, $core);

$message_map = new message_map();

// setup the templating, if not logged in default theme, if logged in use selected theme
$templating = new template($core, $core->config('template'));

$filecache = new file_cache($core);

$announcements_class = new announcements($core, $dbl, $user);

$bbcode = new bbcode($dbl, $core, $user);

$notifications = new notifications($dbl, $core, $bbcode);

$article_class = new article($dbl, $core, $user, $templating, $bbcode);