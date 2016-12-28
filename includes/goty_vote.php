<?php
session_start();
header('Content-Type: application/json');

include('config.php');

include('class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('class_core.php');
$core = new core();

if($_POST)
{
	if (core::config('goty_voting_open') == 1 && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
	{
		$db->sqlquery("SELECT `user_id` FROM `goty_votes` WHERE `user_id` = ? AND `category_id` = ?", array($_SESSION['user_id'], $_POST['category_id']));
		if ($db->num_rows() == 0)
		{
			$db->sqlquery("UPDATE `goty_games` SET `votes` = (votes + 1) WHERE `id` = ?", array($_POST['game_id']));

			$db->sqlquery("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'goty_total_votes'");

			$db->sqlquery("INSERT INTO `goty_votes` SET `user_id` = ?, `game_id` = ?, `category_id` = ?", array($_SESSION['user_id'], $_POST['game_id'], $_POST['category_id']));
			echo json_encode(array("result" => 1));
			return;
		}
		else
		{
			echo json_encode(array("result" => 3));
			return;
		}
	}
	else if (core::config('goty_voting_open') == 0)
	{
		echo json_encode(array("result" => 2));
		return;
	}
}
?>
