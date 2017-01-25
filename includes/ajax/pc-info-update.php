<?php
session_start();
header('Content-Type: application/json');

include('../config.php');

include('../class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../class_core.php');
$core = new core();

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	$db->sqlquery("UPDATE `user_profile_info` SET `date_updated` = ? WHERE `user_id` = ?", array(gmdate("Y-n-d H:i:s"), $_SESSION['user_id']));

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
