<?php
define("APP_ROOT", dirname(__FILE__));

include(APP_ROOT . '/includes/header.php');

if ($core->config('goty_page_open') == 0)
{
	if ($user->check_group([1,2,5]) == false)
	{
		header("Location: /index.php");
		die();
	}
	else
	{
		$core->message('The GOTY page is currently turned off, this is only accessible by editors!', 1);
	}
}

$templating->set_previous('title', 'Linux Game Of The Year Awards', 1);
$templating->set_previous('meta_description', 'Vote for your favourite Linux game of the past year', 1);
$templating->load('goty');

$templating->block('vote_popover', 'goty');

$templating->block('main', 'goty');

if (isset($_SESSION['message']))
{
	$extra = NULL;
	if (isset($_SESSION['message_extra']))
	{
		$extra = $_SESSION['message_extra'];
	}
	$message_map->display_message('goty', $_SESSION['message'], $extra);
}

if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0))
{
	$templating->block('login');
}

$goty_modules = ['home', 'category', 'top10', 'direct'];

if (isset($_GET['module']) && in_array($_GET['module'], $goty_modules))
{
	include(APP_ROOT . '/goty_modules/' . $_GET['module'] . '.php');
}
else
{
	include(APP_ROOT . '/goty_modules/home.php');
}

// add game
if (isset($_POST['act']))
{
	if ($_POST['act'] == 'add')
	{
		if ($core->config('goty_games_open') == 1)
		{
			if (!empty($_POST['game_id']))
			{
				// check if it exists
				$check = $dbl->run("SELECT `game_id` FROM `goty_games` WHERE `game_id` = ? AND `category_id` = ?", array($_POST['game_id'], $_POST['category']))->fetchOne();

				// add it
				if (!$check)
				{
					if ($user->check_group([1,2,5]) == false)
					{
						$dbl->run("INSERT INTO `goty_games` SET `game_id` = ?, `category_id` = ?", array($_POST['game_id'], $_POST['category']));
						$game_id = $dbl->new_id();

						$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `type` = ?, `completed` = 0, `created_date` = ?, `data` = ?", array($_SESSION['user_id'], 'goty_game_submission', core::$date, $game_id));

						$_SESSION['message'] = 'goty_game_submitted';
						header("Location: " . $core->config('website_url') . "goty.php");
					}
					else if ($user->check_group([1,2,5]) == true)
					{
						$dbl->run("INSERT INTO `goty_games` SET `game_id` = ?, `category_id` = ?, `accepted` = 1", array($_POST['game_id'], $_POST['category']));
						$game_id = $dbl->new_id();

						$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `type` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], 'goty_game_added', core::$date, core::$date, $game_id));

						$_SESSION['message'] = 'goty_added_editor';
						header("Location: " . $core->config('website_url') . "goty.php");
					}
				}

				else
				{
					$_SESSION['message'] = 'game_exists';
					header("Location: " . $core->config('website_url') . "goty.php");
				}
			}
			else
			{
				$_SESSION['message'] = 'empty';
				$_SESSION['message_extra'] = 'game name';
				header("Location: " . $core->config('website_url') . "goty.php");
			}
		}

		else
		{
			header("Location: " . $core->config('website_url') . "goty.php");
		}
	}

	if ($_POST['act'] == 'reset_category_vote')
	{
		if ($core->config('goty_voting_open') == 1 && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			if (!empty($_POST['category_id']))
			{
				$check_vote = $dbl->run("SELECT `user_id`, `id` FROM `goty_votes` WHERE `category_id` = ? AND `user_id` = ?", array($_POST['category_id'], $_SESSION['user_id']))->fetch();
				if ($check_vote)
				{
					$dbl->run("DELETE FROM `goty_votes` WHERE `category_id` = ? AND `user_id` = ?", array($_POST['category_id'], $_SESSION['user_id']));

					$_SESSION['message'] = 'goty_vote_deleted';
					header("Location: /goty.php?category_id=".$_POST['category_id']);
				}
			}
		}
		else
		{
			header("Location: /goty.php");
			die();
		}
	}
}

$templating->block('bottom', 'goty');

include(APP_ROOT . '/includes/footer.php');
