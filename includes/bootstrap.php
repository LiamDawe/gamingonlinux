<?php
require dirname(__FILE__) . "/loader.php";
include(dirname(__FILE__) . '/PHPMailer/PHPMailerAutoload.php');

$db_conf = include dirname(__FILE__) . '/config.php';

$dbl = new db_mysql();

$core = new core();
define('url', $core->config('website_url'));

$message_map = new message_map();

// setup the templating, if not logged in default theme, if logged in use selected theme
$templating = new template($core, $core->config('template'));

$user = new user($core);

$bbcode = new bbcode($dbl, $core);

$article_class = new article($core, $user, $templating, $bbcode);
