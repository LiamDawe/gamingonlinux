<?php
session_start();

header('Content-Type: application/json');

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if($_POST)
{
	// make sure the poll is open
	$check = $dbl->run("SELECT `poll_open` FROM `polls` WHERE `poll_id` = ? AND `author_id` = ?", array($_POST['poll_id'], $_SESSION['user_id']))->fetchOne();
	if ($check == 1)
	{
			$dbl->run("UPDATE `polls` SET `poll_open` = 0 WHERE `poll_id` = ?", array($_POST['poll_id']));

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
