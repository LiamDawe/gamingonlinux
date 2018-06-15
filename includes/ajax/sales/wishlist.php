<?php
session_start();

header('Content-Type: application/json');

define("APP_ROOT", dirname( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if (isset($_GET['game_id']))
{
	if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
	{
		if (isset($_GET['type']) && $_GET['type'] == 'add' || $_GET['type'] == 'remove')
		{
			if ($_GET['type'] == 'add')
			{
				// check it doesn't exist already
				$test = $dbl->run("SELECT 1 FROM `user_wishlist` WHERE `game_id` = ? AND `user_id` = ?", array($_GET['game_id'], $_SESSION['user_id']))->fetch();
				if ($test)
				{
					return false;
				}
				else
				{
					$dbl->run("INSERT INTO `user_wishlist` SET `game_id` = ?, `user_id` = ?", array($_GET['game_id'], $_SESSION['user_id']));
					echo json_encode(array("message" => 'Added'));
					return;
				}
			}
			if ($_GET['type'] == 'remove')
			{
				$dbl->run("DELETE FROM `user_wishlist` WHERE `game_id` = ? AND `user_id` = ?", array($_GET['game_id'], $_SESSION['user_id']));
				echo json_encode(array("message" => 'Removed'));
				return;
			}
		}
		else
		{
			return false;
		}
	}
	else
	{
		return false;
	}
}