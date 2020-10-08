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

$templating = new template($core, $core->config('template'));

$user_session = new session($dbl, $core, $templating);

$filecache = new file_cache($core);

$announcements_class = new announcements($core, $dbl, $user);

$bbcode = new bbcode($dbl, $core, $user);

$gamedb = new game_sales($dbl, $templating, $user, $core, $bbcode);

$notifications = new notifications($dbl, $core, $bbcode);

$article_class = new article($dbl, $core, $user, $templating, $bbcode, $notifications);