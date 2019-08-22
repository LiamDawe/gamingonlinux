<?php
session_start();
header('Content-Type: application/json');

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if($_POST)
{
	// make sure the poll is open
	$checker = $dbl->run("SELECT `poll_open` FROM `polls` WHERE `poll_id` = ? AND `author_id` = ?", array($_POST['poll_id'], $_SESSION['user_id']))->fetchOne();
	if ($checker == 1 || $user->check_group([1]))
	{
			$dbl->run("UPDATE `polls` SET `poll_open` = 1 WHERE `poll_id` = ?", array($_POST['poll_id']));

			// find if they can vote or not to show the correct page
			$voted = $dbl->run("SELECT `user_id`, `option_id` FROM `poll_votes` WHERE `user_id` = ? AND `poll_id` = ?", array($_SESSION['user_id'], $_POST['poll_id']))->fetchOne();
			if ($voted)
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
