<?php
include('/home/gamingonlinux/public_html/includes/config.php');

include('/home/gamingonlinux/public_html/includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('/home/gamingonlinux/public_html/includes/class_core.php');
$core = new core();

include('/home/gamingonlinux/public_html/includes/bbcode.php');

// setup the templating, if not logged in default theme, if logged in use selected theme
include('/home/gamingonlinux/public_html/includes/class_template.php');

$templating = new template();

$year = 365*24*60*60;

// remove completed admin notifications older than 1 year, clean up the cruft
$db->sqlquery("DELETE FROM `admin_notifications` WHERE `completed` = 1 AND created_date <= (created_date - $year)");
?>
