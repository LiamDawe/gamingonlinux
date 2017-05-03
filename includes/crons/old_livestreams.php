<?php
// remove livestreams that have been finished
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

$timeout = 86400; // 1 day

$stamp = time() - $timeout;

$sql_date = date('Y/m/d H:i:s', $stamp);

$dbl->run("DELETE FROM `livestreams` WHERE `end_date` <= ?", array($sql_date));
