<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

$timeout = 1800; // 30 minutes

$stamp = time() - $timeout;

$db->sqlquery("SELECT `article_id`, `locked_date` FROM `articles` WHERE `locked_date` <= ? AND `locked_date` != 0", array($stamp));
$locked = $db->fetch_all_rows();

foreach($locked as $row)
{
	$db->sqlquery("UPDATE `articles` SET `locked` = 0, `locked_date` = 0, `locked_by` = 0 WHERE `article_id` = ?", array($row['article_id']));
}
