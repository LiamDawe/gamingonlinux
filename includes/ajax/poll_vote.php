<?php
session_start();

header('Content-Type: application/json');

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if($_POST)
{
	// make sure the poll is open
	$open = $dbl->run("SELECT `poll_open` FROM `polls` WHERE `poll_id` = ?", array($_POST['poll_id']))->fetchOne();
	if ($open == 1)
	{
		// make sure they haven't voted already
		$voted = $dbl->run("SELECT `user_id` FROM `poll_votes` WHERE `poll_id` = ? AND `user_id` = ?", array($_POST['poll_id'], $_SESSION['user_id']))->fetchOne();
		if (!$voted)
		{
			// add their vote in
			$dbl->run("INSERT INTO `poll_votes` SET `poll_id` = ?, `option_id` = ?, `user_id` = ?", array($_POST['poll_id'], $_POST['option_id'], $_SESSION['user_id']));

			// add to the total of this option
			$dbl->run("UPDATE `poll_options` SET `votes` = (votes + 1) WHERE `option_id` = ?", array($_POST['option_id']));

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
