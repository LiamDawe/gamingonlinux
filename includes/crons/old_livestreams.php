<?php
// remove livestreams that have been finished
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

$timeout = 86400; // 1 day

$stamp = time() - $timeout;

$sql_date = date('Y/m/d H:i:s', $stamp);

$db->sqlquery("DELETE FROM `livestreams` WHERE `end_date` <= ?", array($sql_date));
