<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

// setup the templating, if not logged in default theme, if logged in use selected theme
include($file_dir . '/includes/class_template.php');

$templating = new template();

$year = 365*24*60*60;

// remove completed admin notifications older than 1 year, clean up the cruft
$db->sqlquery("DELETE FROM `admin_notifications` WHERE `completed` = 1 AND created_date <= (created_date - $year)");
?>
