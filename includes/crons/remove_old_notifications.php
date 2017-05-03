<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

$year = 365*24*60*60;

// remove completed admin notifications older than 1 year, clean up the cruft
$dbl->run("DELETE FROM `admin_notifications` WHERE `completed` = 1 AND created_date <= (created_date - $year)");
?>
