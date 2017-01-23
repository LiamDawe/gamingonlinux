<?php
// remove livestreams that have been finished
$path = '/home/gamingonlinux/public_html/';
include($path . 'includes/config.php');

include($path . 'includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include($path . 'includes/class_core.php');
$core = new core();

$timeout = 86400; // 1 day

$stamp = time() - $timeout;

$sql_date = date('Y/m/d H:i:s', $stamp);

$db->sqlquery("DELETE FROM `livestreams` WHERE `end_date` <= ?", array($sql_date));
