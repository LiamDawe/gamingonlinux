<?php
session_start();
header('Content-Type: application/json');

$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'],$db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

if($_POST)
{
	if ($core->config('goty_voting_open') == 1 && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
	{
		$check_vote = $dbl->run("SELECT `user_id` FROM `goty_votes` WHERE `user_id` = ? AND `category_id` = ?", array($_SESSION['user_id'], $_POST['category_id']))->fetchOne();
		if (!$check_vote)
		{
			$dbl->run("UPDATE `goty_games` SET `votes` = (votes + 1) WHERE `id` = ?", array($_POST['game_id']));

			$dbl->run("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'goty_total_votes'");

			$dbl->run("INSERT INTO `goty_votes` SET `user_id` = ?, `game_id` = ?, `category_id` = ?", array($_SESSION['user_id'], $_POST['game_id'], $_POST['category_id']));
			echo json_encode(array("result" => 1));
			return;
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
