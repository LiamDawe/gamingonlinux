<?php
session_start();
header('Content-Type: application/json');

define("APP_ROOT", dirname ( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if($_POST)
{
	if ($core->config('goty_voting_open') == 1 && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
	{
		$check_vote = $dbl->run("SELECT `game_id` FROM `goty_votes` WHERE `user_id` = ? AND `category_id` = ?", array($_SESSION['user_id'], $_POST['category_id']))->fetch_all();
		$total_votes = count($check_vote);

		if (!$check_vote || $total_votes < $core->config('goty_votes_per_category'))
		{
			if (!in_array($_POST['game_id'], $check_vote))
			{
				$dbl->run("UPDATE `goty_games` SET `votes` = (votes + 1) WHERE `id` = ?", array($_POST['game_id']));

				$dbl->run("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'goty_total_votes'");

				$dbl->run("INSERT INTO `goty_votes` SET `user_id` = ?, `game_id` = ?, `category_id` = ?", array($_SESSION['user_id'], $_POST['game_id'], $_POST['category_id']));
				echo json_encode(array("result" => 1));
				return;
			}
			else
			{
				echo json_encode(array("result" => 4));
				return;
			}
		}
		else
		{
			echo json_encode(array("result" => 3));
			return;
		}
	}
	else if ($core->config('goty_voting_open') == 0)
	{
		echo json_encode(array("result" => 2));
		return;
	}
}
?>
