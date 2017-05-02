<?php
session_start();
header('Content-Type: application/json');

$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	$dbl->run("UPDATE `user_profile_info` SET `date_updated` = ? WHERE `user_id` = ?", array(gmdate("Y-n-d H:i:s"), $_SESSION['user_id']));

	echo json_encode(array("result" => 1));
	return;
}
// not logged in
else
{
	echo json_encode(array("result" => 2));
	return;
}
?>
