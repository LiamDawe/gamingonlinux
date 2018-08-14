<?php
header('Content-Type: application/json');

session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if($_POST)
{
	if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
	{
		// get some info about the giveaway
		$giveaway = $dbl->run("SELECT `supporters_only` FROM `game_giveaways` WHERE `id` = ?", array($_POST['giveaway_id']))->fetch();

		if ($giveaway['supporters_only'] == 1)
		{
			if (!$user->check_group(9))
			{
				echo json_encode(array("result" => 4));
				return;
			}
		}

		// for grabbing a single key from a single game
		if (!isset($_POST['key_id']) || isset($_POST['key_id']) && $_POST['key_id'] == null)
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
		// for grabbing a single game from multiple choices
		else
		{
			// see if they've grabbed one first
			$check = $dbl->run("SELECT 1 FROM `game_giveaways_keys` WHERE `game_id` = ? AND `claimed_by_id` = ?", array($_POST['giveaway_id'], $_SESSION['user_id']))->fetch();

			if (!$check)
			{
				$claimed_key = $dbl->run("SELECT `id`, `name`, `game_key`, `claimed` FROM `game_giveaways_keys` WHERE `game_id` = ? AND `id` = ?", array($_POST['giveaway_id'], $_POST['key_id']))->fetch();

				if ($claimed_key['claimed'] == 1)
				{
					echo json_encode(array("result" => 5));
					return;				
				}
				else
				{
					$dbl->run("UPDATE `game_giveaways_keys` SET `claimed` = 1, `claimed_by_id` = ?, `claimed_date` = ? WHERE `id` = ?", array($_SESSION['user_id'], core::$date, $claimed_key['id']));

					$return_array = array("result" => 1, "key" => "Awesome! You've bagged yourself something! " . $claimed_key['name'] . ' - ' . $claimed_key['game_key']);

					echo json_encode($return_array);
					return;				
				}
			}
			else
			{
				echo json_encode(array("result" => 6));
				return;			
			}
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
