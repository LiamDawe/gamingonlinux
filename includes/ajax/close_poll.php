<?php
session_start();
header('Content-Type: application/json');

include('../config.php');

include('../class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../class_core.php');
$core = new core();

if($_POST)
{
	// make sure the poll is open
	$db->sqlquery("SELECT `poll_open` FROM `polls` WHERE `poll_id` = ? AND `author_id` = ?", array($_POST['poll_id'], $_SESSION['user_id']));
	if ($db->num_rows() == 1)
	{
			$db->sqlquery("UPDATE `polls` SET `poll_open` = 0 WHERE `poll_id` = ?", array($_POST['poll_id']));

			echo json_encode(array("result" => 1));
			return;
	}
	else
	{
		echo json_encode(array("result" => 2));
		return;
	}
}
?>
