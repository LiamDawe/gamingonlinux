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
	if (core::config('goty_voting_open') == 1)
	{
		$db->sqlquery("SELECT `ip` FROM `goty_votes` WHERE `ip` = ? AND `category_id` = ?", array($core->ip, $_POST['category_id']));
		if ($db->num_rows() == 0)
		{
			$db->sqlquery("SELECT `game` FROM `goty_games` WHERE `id` = ?", array($_POST['game_id']));
			$game = $db->fetch();

			$db->sqlquery("UPDATE `goty_games` SET `votes` = (votes + 1) WHERE `id` = ?", array($_POST['game_id']));

			$db->sqlquery("UPDATE `config` SET `data_value` = (data_value +1) WHERE `data_key` = 'goty_total_votes'");

			$db->sqlquery("INSERT INTO `goty_votes` SET `ip` = ?, `game_id` = ?, `category_id` = ?", array($core->ip, $_POST['game_id'], $_POST['category_id']));
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
