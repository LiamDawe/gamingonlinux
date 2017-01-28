<?php
header('Content-Type: application/json');

$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

if($_POST)
{
	// make sure the poll is open
	$db->sqlquery("SELECT `poll_open` FROM `polls` WHERE `poll_id` = ? AND `author_id` = ?", array($_POST['poll_id'], $_SESSION['user_id']));
	if ($db->num_rows() == 1)
	{
			$db->sqlquery("UPDATE `polls` SET `poll_open` = 1 WHERE `poll_id` = ?", array($_POST['poll_id']));

			// find if they can vote or not to show the correct page
			$db->sqlquery("SELECT `user_id`, `option_id` FROM `poll_votes` WHERE `user_id` = ? AND `poll_id` = ?", array($_SESSION['user_id'], $_POST['poll_id']));
			if ($db->num_rows() == 1)
			{
				echo json_encode(array("result" => 1));
				return;
			}
			else
			{
				echo json_encode(array("result" => 2));
				return;
			}

	}
	else
	{
		echo json_encode(array("result" => 3));
		return;
	}
}
?>
