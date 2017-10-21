<?php
require dirname(__FILE__) . "/loader.php";
include(dirname(__FILE__) . '/PHPMailer/PHPMailerAutoload.php');

$db_conf = include dirname(__FILE__) . '/config.php';

$db = new mysql($db_conf['host'], $db_conf['username'], $db_conf['password'], $db_conf['database']);
$dbl = new db_mysql();

$core = new core();
define('url', $core->config('website_url'));

$message_map = new message_map();

// setup the templating, if not logged in default theme, if logged in use selected theme
$templating = new template($core, $core->config('template'));

$user = new user($core);

$bbcode = new bbcode($dbl, $core);

$article_class = new article($dbl, $core, $user, $templating, $bbcode);
