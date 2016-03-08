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
	$db->sqlquery("SELECT `poll_open` FROM `polls` WHERE `poll_id` = ?", array($_POST['poll_id']));
	if ($db->num_rows() == 1)
	{
		// make sure they haven't voted already
		$db->sqlquery("SELECT `user_id` FROM `poll_votes` WHERE `poll_id` = ? AND `user_id` = ?", array($_POST['poll_id'], $_SESSION['user_id']));
		if ($db->num_rows() == 0)
		{
			// add their vote in
			$db->sqlquery("INSERT INTO `poll_votes` SET `poll_id` = ?, `option_id` = ?, `user_id` = ?", array($_POST['poll_id'], $_POST['option_id'], $_SESSION['user_id']));

			// add to the total of this option
			$db->sqlquery("UPDATE `poll_options` SET `votes` = (votes + 1) WHERE `option_id` = ?", array($_POST['option_id']));

			echo json_encode(array("result" => 1));
			return;
		}
		else
		{
			echo json_encode(array("result" => 3));
			return;
		}
	}
	else
	{
		echo json_encode(array("result" => 2));
		return;
	}
}
?>
