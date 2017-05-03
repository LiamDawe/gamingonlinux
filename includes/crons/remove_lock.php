<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

$timeout = 1800; // 30 minutes

$stamp = time() - $timeout;

$locked = $dbl->run("SELECT `article_id`, `locked_date` FROM `articles` WHERE `locked_date` <= ? AND `locked_date` != 0", array($stamp))->fetch_all();

foreach($locked as $row)
{
	$dbl->run("UPDATE `articles` SET `locked` = 0, `locked_date` = 0, `locked_by` = 0 WHERE `article_id` = ?", array($row['article_id']));
}
