<?php
header('Content-Type: application/json');

session_start();

$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'],$db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

if($_POST)
{
	if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
	{
		$keys_left = $dbl->run("SELECT COUNT(id) as counter FROM `game_giveaways_keys` WHERE `claimed` = 0 AND `game_id` = ?", array($_POST['giveaway_id']))->fetchOne();
		if ($keys_left > 0)
		{
			$your_key = $dbl->run("SELECT COUNT(game_key) as counter, `game_key` FROM `game_giveaways_keys` WHERE `claimed_by_id` = ? AND `game_id` = ? GROUP BY `game_key`", array($_SESSION['user_id'], $_POST['giveaway_id']))->fetch();

			// they are keys left and they haven't taken one
			if (!isset($your_key['counter']))
			{
				$claimed_key = $dbl->run("SELECT `id`, `game_key` FROM `game_giveaways_keys` WHERE `game_id` = ? AND `claimed` = 0 ORDER BY rand()", array($_POST['giveaway_id']))->fetch();
				$dbl->run("UPDATE `game_giveaways_keys` SET `claimed` = 1, `claimed_by_id` = ?, `claimed_date` = ? WHERE `id` = ?", array($_SESSION['user_id'], core::$date, $claimed_key['id']));

				$return_array = array("result" => 1, "key" => $claimed_key['game_key']);

				echo json_encode($return_array);
				return;
			}
		}
		// no keys left
		else
		{
			echo json_encode(array("result" => 2));
			return;
		}
	}
	// not logged in
	else
	{
		echo json_encode(array("result" => 3));
		return;
	}
}
?>
